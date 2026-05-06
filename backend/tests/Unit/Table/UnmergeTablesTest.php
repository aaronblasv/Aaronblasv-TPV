<?php

declare(strict_types=1);

namespace Tests\Unit\Table;

use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Application\UnmergeTables\UnmergeTables;
use App\Table\Domain\Entity\Table;
use App\Table\Domain\Exception\InvalidTableMergeException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Domain\ValueObject\TableName;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UnmergeTablesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_unmerges_all_children_from_parent(): void
    {
        $restaurantId = 1;
        $parentTableUuid = Uuid::generate()->getValue();

        $parentTable = Table::dddCreate(
            Uuid::create($parentTableUuid),
            TableName::create('Mesa principal'),
            Uuid::generate(),
            $restaurantId,
        );

        $firstChild = Table::dddCreate(
            Uuid::generate(),
            TableName::create('Mesa 2'),
            Uuid::generate(),
            $restaurantId,
        );
        $firstChild->mergeWith(Uuid::create($parentTableUuid));

        $secondChild = Table::dddCreate(
            Uuid::generate(),
            TableName::create('Mesa 3'),
            Uuid::generate(),
            $restaurantId,
        );
        $secondChild->mergeWith(Uuid::create($parentTableUuid));

        $repository = Mockery::mock(TableRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($parentTableUuid, $restaurantId)->andReturn($parentTable);
        $repository->shouldReceive('findByMergedWith')->once()->with($parentTableUuid, $restaurantId)->andReturn([$firstChild, $secondChild]);
        $repository->shouldReceive('update')->twice()->with(Mockery::on(
            fn (Table $table): bool => in_array($table->name()->getValue(), ['Mesa 2', 'Mesa 3'], true)
                && $table->mergedWith() === null
        ));

        $useCase = new UnmergeTables($repository);

        $useCase($parentTableUuid, $restaurantId);
    }

    public function test_it_rejects_unmerge_from_child_table(): void
    {
        $restaurantId = 1;
        $childTableUuid = Uuid::generate()->getValue();

        $childTable = Table::dddCreate(
            Uuid::create($childTableUuid),
            TableName::create('Mesa hija'),
            Uuid::generate(),
            $restaurantId,
        );
        $childTable->mergeWith(Uuid::generate());

        $repository = Mockery::mock(TableRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($childTableUuid, $restaurantId)->andReturn($childTable);
        $repository->shouldNotReceive('findByMergedWith');
        $repository->shouldNotReceive('update');

        $useCase = new UnmergeTables($repository);

        $this->expectException(InvalidTableMergeException::class);
        $this->expectExceptionMessage('Solo se puede desagrupar desde la mesa principal.');

        $useCase($childTableUuid, $restaurantId);
    }

    public function test_it_rejects_unmerge_when_parent_has_no_children(): void
    {
        $restaurantId = 1;
        $parentTableUuid = Uuid::generate()->getValue();

        $parentTable = Table::dddCreate(
            Uuid::create($parentTableUuid),
            TableName::create('Mesa principal'),
            Uuid::generate(),
            $restaurantId,
        );

        $repository = Mockery::mock(TableRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($parentTableUuid, $restaurantId)->andReturn($parentTable);
        $repository->shouldReceive('findByMergedWith')->once()->with($parentTableUuid, $restaurantId)->andReturn([]);
        $repository->shouldNotReceive('update');

        $useCase = new UnmergeTables($repository);

        $this->expectException(InvalidTableMergeException::class);
        $this->expectExceptionMessage('La mesa indicada no tiene mesas agrupadas.');

        $useCase($parentTableUuid, $restaurantId);
    }
}
