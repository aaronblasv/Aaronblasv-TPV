<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\CloseOrder\CloseOrder;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\Diners;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CloseOrderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_close_order_logs_selected_waiter_as_actor(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $authenticatedUserUuid = Uuid::generate()->getValue();
        $closedByUserUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, $authenticatedUserUuid, '127.0.0.1');

        $order = Order::dddCreate(
            Uuid::create($orderUuid),
            $restaurantId,
            Uuid::generate(),
            Uuid::generate(),
            Diners::create(2),
        );

        $line = Mockery::mock();
        $line->shouldReceive('subtotalAfterDiscount')->andReturn(1000);
        $line->shouldReceive('taxAmount')->andReturn(100);
        $line->shouldReceive('discountAmount')->andReturn(0);
        $line->shouldReceive('uuid')->andReturn(Uuid::generate());
        $line->shouldReceive('userId')->andReturn(Uuid::generate());
        $line->shouldReceive('quantity->getValue')->andReturn(1);
        $line->shouldReceive('price')->andReturn(1000);
        $line->shouldReceive('taxPercentage')->andReturn(10);
        $line->shouldReceive('subtotal')->andReturn(1000);
        $line->shouldReceive('discountType')->andReturn(null);
        $line->shouldReceive('discountValue')->andReturn(0);
        $line->shouldReceive('total')->andReturn(1100);

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findById')->once()->with($orderUuid, $restaurantId)->andReturn($order);
        $orderRepository->shouldReceive('update')->once()->with(Mockery::on(
            fn (Order $updatedOrder) => $updatedOrder->uuid()->getValue() === $orderUuid
                && $updatedOrder->status()->isClosed()
                && $updatedOrder->closedByUserId()?->getValue() === $closedByUserUuid
        ));

        $lineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $lineRepository->shouldReceive('findAllByOrderId')->once()->with($orderUuid, $restaurantId)->andReturn([$line]);
        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once()->withArgs(function (...$events) use ($closedByUserUuid, $orderUuid) {
            foreach ($events as $event) {
                if ($event instanceof ActionLogged) {
                    return $event->action === 'order.closed'
                        && $event->userId === $closedByUserUuid
                        && $event->entityUuid === $orderUuid;
                }
            }

            return false;
        });

        $useCase = new CloseOrder(
            $orderRepository,
            $lineRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $useCase($auditContext, $orderUuid, $closedByUserUuid);
    }

    public function test_close_order_requests_single_ticket_number_for_the_sale_flow(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $authenticatedUserUuid = Uuid::generate()->getValue();
        $closedByUserUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, $authenticatedUserUuid, '127.0.0.1');

        $order = Order::dddCreate(
            Uuid::create($orderUuid),
            $restaurantId,
            Uuid::generate(),
            Uuid::generate(),
            Diners::create(2),
        );

        $line = Mockery::mock();
        $line->shouldReceive('subtotalAfterDiscount')->andReturn(1000);
        $line->shouldReceive('taxAmount')->andReturn(100);
        $line->shouldReceive('discountAmount')->andReturn(0);
        $line->shouldReceive('uuid')->andReturn(Uuid::generate());
        $line->shouldReceive('userId')->andReturn(Uuid::generate());
        $line->shouldReceive('quantity->getValue')->andReturn(1);
        $line->shouldReceive('price')->andReturn(1000);
        $line->shouldReceive('taxPercentage')->andReturn(10);
        $line->shouldReceive('subtotal')->andReturn(1000);
        $line->shouldReceive('discountType')->andReturn(null);
        $line->shouldReceive('discountValue')->andReturn(0);
        $line->shouldReceive('total')->andReturn(1100);

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findById')->once()->with($orderUuid, $restaurantId)->andReturn($order);
        $orderRepository->shouldReceive('update')->once();

        $lineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $lineRepository->shouldReceive('findAllByOrderId')->once()->with($orderUuid, $restaurantId)->andReturn([$line]);
        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once();

        $useCase = new CloseOrder(
            $orderRepository,
            $lineRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $response = $useCase($auditContext, $orderUuid, $closedByUserUuid);

        $this->assertSame($orderUuid, $response->uuid);
        $this->assertSame('closed', $response->status);
        $this->assertSame(1100, $response->total);
        $this->assertSame($closedByUserUuid, $response->closedByUserId);
        $this->assertArrayNotHasKey('ticket_number', $response->toArray());
    }

    private function transactionManager(): TransactionManagerInterface
    {
        return new class implements TransactionManagerInterface {
            public function run(callable $callback): mixed
            {
                return $callback();
            }
        };
    }
}
