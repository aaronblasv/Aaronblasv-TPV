<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\UpdateOrderLineQuantity\UpdateOrderLineQuantity;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Exception\CannotModifyPaidOrderLineException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateOrderLineQuantityTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_rejects_updating_a_paid_order_line(): void
    {
        $restaurantId = 1;
        $lineUuid = Uuid::generate()->getValue();
        $line = OrderLine::dddCreate(
            Uuid::create($lineUuid),
            $restaurantId,
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Quantity::create(2),
            1250,
            10,
            null,
            0,
            0,
            null,
            DomainDateTime::now(),
        );

        $repository = Mockery::mock(OrderLineRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($lineUuid, $restaurantId)->andReturn($line);
        $repository->shouldNotReceive('update');

        $useCase = new UpdateOrderLineQuantity($repository);

        $this->expectException(CannotModifyPaidOrderLineException::class);

        $useCase($lineUuid, 1, $restaurantId);
    }
}