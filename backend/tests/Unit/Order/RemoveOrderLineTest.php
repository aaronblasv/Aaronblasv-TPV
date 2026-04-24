<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\RemoveOrderLine\RemoveOrderLine;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Exception\CannotRemoveSentToKitchenOrderLineException;
use App\Order\Domain\Exception\OrderLineNotFoundInOrderContextException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RemoveOrderLineTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_rejects_removing_a_line_already_sent_to_kitchen(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $lineUuid = Uuid::generate()->getValue();

        $line = $this->makeLine($orderUuid, true);

        $repository = Mockery::mock(OrderLineRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($lineUuid, $restaurantId)->andReturn($line);
        $repository->shouldNotReceive('delete');

        $useCase = new RemoveOrderLine($repository);

        $this->expectException(CannotRemoveSentToKitchenOrderLineException::class);

        $useCase($orderUuid, $lineUuid, $restaurantId);
    }

    public function test_it_rejects_removing_a_line_from_another_order_context(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $otherOrderUuid = Uuid::generate()->getValue();
        $lineUuid = Uuid::generate()->getValue();

        $line = $this->makeLine($otherOrderUuid, false);

        $repository = Mockery::mock(OrderLineRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($lineUuid, $restaurantId)->andReturn($line);
        $repository->shouldNotReceive('delete');

        $useCase = new RemoveOrderLine($repository);

        $this->expectException(OrderLineNotFoundInOrderContextException::class);

        $useCase($orderUuid, $lineUuid, $restaurantId);
    }

    private function makeLine(string $orderUuid, bool $sentToKitchen): OrderLine
    {
        return OrderLine::dddCreate(
            Uuid::generate(),
            1,
            Uuid::create($orderUuid),
            Uuid::generate(),
            Uuid::generate(),
            Quantity::create(1),
            1200,
            10,
            null,
            0,
            0,
            $sentToKitchen ? DomainDateTime::now() : null,
        );
    }
}
