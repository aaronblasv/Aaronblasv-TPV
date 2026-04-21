<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\CancelOrder\CancelOrder;
use App\Order\Domain\Entity\Order;
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

class CancelOrderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_cancel_order_uses_domain_transition_and_dispatches_audit_log(): void
    {
        $orderUuid = Uuid::generate()->getValue();
        $restaurantId = 1;
        $auditContext = new AuditContext($restaurantId, Uuid::generate()->getValue(), '127.0.0.1');

        $order = Order::dddCreate(
            Uuid::create($orderUuid),
            $restaurantId,
            Uuid::generate(),
            Uuid::generate(),
            Diners::create(2),
        );

        $repository = Mockery::mock(OrderRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($orderUuid, $restaurantId)->andReturn($order);
        $repository->shouldReceive('update')->once()->with(Mockery::on(function (Order $updatedOrder) use ($orderUuid) {
            return $updatedOrder->uuid()->getValue() === $orderUuid
                && $updatedOrder->status()->isCancelled();
        }));
        $repository->shouldNotReceive('delete');

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once()->with(Mockery::type(ActionLogged::class));

        $useCase = new CancelOrder(
            $repository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $useCase($auditContext, $orderUuid);
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