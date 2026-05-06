<?php

declare(strict_types=1);

namespace Tests\Unit\Table;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Application\MergeTables\MergeTables;
use App\Table\Domain\Entity\Table;
use App\Table\Domain\Exception\InvalidTableMergeException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Domain\ValueObject\TableName;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class MergeTablesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_marks_empty_child_tables_as_merged_into_parent(): void
    {
        $restaurantId = 1;
        $parentTableUuid = Uuid::generate()->getValue();
        $childTableUuid = Uuid::generate()->getValue();

        $parentTable = Table::dddCreate(
            Uuid::create($parentTableUuid),
            TableName::create('Mesa principal'),
            Uuid::generate(),
            $restaurantId,
        );

        $childTable = Table::dddCreate(
            Uuid::create($childTableUuid),
            TableName::create('Mesa auxiliar'),
            Uuid::generate(),
            $restaurantId,
        );

        $survivorOrder = Order::fromPersistence(
            Uuid::generate()->getValue(),
            $restaurantId,
            'open',
            $parentTableUuid,
            Uuid::generate()->getValue(),
            null,
            2,
            'percentage',
            10,
            0,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
            null,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
        );

        $tableRepository = Mockery::mock(TableRepositoryInterface::class);
        $tableRepository->shouldReceive('findById')->once()->with($parentTableUuid, $restaurantId)->andReturn($parentTable);
        $tableRepository->shouldReceive('findByMergedWith')->once()->with($parentTableUuid, $restaurantId)->andReturn([]);
        $tableRepository->shouldReceive('findById')->once()->with($childTableUuid, $restaurantId)->andReturn($childTable);
        $tableRepository->shouldReceive('findByMergedWith')->once()->with($childTableUuid, $restaurantId)->andReturn([]);
        $tableRepository->shouldReceive('update')->once()->with(Mockery::on(
            fn (Table $updatedTable): bool => $updatedTable->uuid()->getValue() === $childTableUuid
                && $updatedTable->mergedWith()?->getValue() === $parentTableUuid
        ));

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findOpenByTableId')->once()->with($parentTableUuid, $restaurantId)->andReturn($survivorOrder);
        $orderRepository->shouldReceive('findOpenByTableId')->once()->with($childTableUuid, $restaurantId)->andReturn(null);
        $orderRepository->shouldNotReceive('update');
        $orderRepository->shouldNotReceive('delete');

        $useCase = new MergeTables(
            $tableRepository,
            $orderRepository,
            new class implements TransactionManagerInterface
            {
                public function run(callable $callback): mixed
                {
                    return $callback();
                }
            },
        );

        $useCase($parentTableUuid, [$childTableUuid], $restaurantId);
    }

    public function test_it_rejects_occupied_child_tables(): void
    {
        $restaurantId = 1;
        $parentTableUuid = Uuid::generate()->getValue();
        $childTableUuid = Uuid::generate()->getValue();

        $parentTable = Table::dddCreate(
            Uuid::create($parentTableUuid),
            TableName::create('Mesa principal'),
            Uuid::generate(),
            $restaurantId,
        );

        $childTable = Table::dddCreate(
            Uuid::create($childTableUuid),
            TableName::create('Mesa ocupada'),
            Uuid::generate(),
            $restaurantId,
        );

        $parentOrder = Order::fromPersistence(
            Uuid::generate()->getValue(),
            $restaurantId,
            'open',
            $parentTableUuid,
            Uuid::generate()->getValue(),
            null,
            2,
            null,
            0,
            0,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
            null,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
        );

        $childOrder = Order::fromPersistence(
            Uuid::generate()->getValue(),
            $restaurantId,
            'open',
            $childTableUuid,
            Uuid::generate()->getValue(),
            null,
            2,
            null,
            0,
            0,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
            null,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
        );

        $tableRepository = Mockery::mock(TableRepositoryInterface::class);
        $tableRepository->shouldReceive('findById')->once()->with($parentTableUuid, $restaurantId)->andReturn($parentTable);
        $tableRepository->shouldReceive('findByMergedWith')->once()->with($parentTableUuid, $restaurantId)->andReturn([]);
        $tableRepository->shouldReceive('findById')->once()->with($childTableUuid, $restaurantId)->andReturn($childTable);
        $tableRepository->shouldReceive('findByMergedWith')->once()->with($childTableUuid, $restaurantId)->andReturn([]);
        $tableRepository->shouldNotReceive('update');

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findOpenByTableId')->once()->with($parentTableUuid, $restaurantId)->andReturn($parentOrder);
        $orderRepository->shouldReceive('findOpenByTableId')->once()->with($childTableUuid, $restaurantId)->andReturn($childOrder);
        $orderRepository->shouldNotReceive('update');
        $orderRepository->shouldNotReceive('delete');

        $useCase = new MergeTables(
            $tableRepository,
            $orderRepository,
            new class implements TransactionManagerInterface
            {
                public function run(callable $callback): mixed
                {
                    return $callback();
                }
            },
        );

        $this->expectException(InvalidTableMergeException::class);
        $this->expectExceptionMessage('No se pueden unir dos o más mesas con pedido abierto. Solo una mesa puede tener pedido y el resto deben estar vacías.');

        $useCase($parentTableUuid, [$childTableUuid], $restaurantId);
    }

    public function test_it_rejects_tables_that_already_belong_to_a_group(): void
    {
        $restaurantId = 1;
        $parentTableUuid = Uuid::generate()->getValue();
        $childTableUuid = Uuid::generate()->getValue();

        $parentTable = Table::dddCreate(
            Uuid::create($parentTableUuid),
            TableName::create('Mesa principal'),
            Uuid::generate(),
            $restaurantId,
        );

        $childTable = Table::dddCreate(
            Uuid::create($childTableUuid),
            TableName::create('Mesa agrupada'),
            Uuid::generate(),
            $restaurantId,
        );
        $childTable->mergeWith(Uuid::generate());

        $parentOrder = Order::fromPersistence(
            Uuid::generate()->getValue(),
            $restaurantId,
            'open',
            $parentTableUuid,
            Uuid::generate()->getValue(),
            null,
            2,
            null,
            0,
            0,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
            null,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
        );

        $tableRepository = Mockery::mock(TableRepositoryInterface::class);
        $tableRepository->shouldReceive('findById')->once()->with($parentTableUuid, $restaurantId)->andReturn($parentTable);
        $tableRepository->shouldReceive('findByMergedWith')->once()->with($parentTableUuid, $restaurantId)->andReturn([]);
        $tableRepository->shouldReceive('findById')->once()->with($childTableUuid, $restaurantId)->andReturn($childTable);
        $tableRepository->shouldNotReceive('update');

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findOpenByTableId')->once()->with($parentTableUuid, $restaurantId)->andReturn($parentOrder);
        $orderRepository->shouldNotReceive('update');
        $orderRepository->shouldNotReceive('delete');

        $useCase = new MergeTables(
            $tableRepository,
            $orderRepository,
            new class implements TransactionManagerInterface
            {
                public function run(callable $callback): mixed
                {
                    return $callback();
                }
            },
        );

        $this->expectException(InvalidTableMergeException::class);
        $this->expectExceptionMessage('No se pueden volver a unir mesas que ya forman parte de una agrupación.');

        $useCase($parentTableUuid, [$childTableUuid], $restaurantId);
    }
}
