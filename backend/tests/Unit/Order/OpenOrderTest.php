<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\CashShift\Domain\Entity\CashShift;
use App\CashShift\Domain\Interfaces\CashShiftRepositoryInterface;
use App\Order\Application\OpenOrder\OpenOrder;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\CannotOpenOrderOnMergedTableException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Entity\Table;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Domain\ValueObject\TableName;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class OpenOrderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_open_order_logs_selected_waiter_as_actor(): void
    {
        $restaurantId = 1;
        $tableUuid = Uuid::generate()->getValue();
        $authenticatedUserUuid = Uuid::generate()->getValue();
        $openedByUserUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, $authenticatedUserUuid, '127.0.0.1');

        $table = Table::dddCreate(
            Uuid::create($tableUuid),
            TableName::create('Mesa 1'),
            Uuid::generate(),
            $restaurantId,
        );

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findOpenByTableId')->once()->with($tableUuid, $restaurantId)->andReturn(null);
        $orderRepository->shouldReceive('save')->once()->with(Mockery::on(
            fn (Order $order) => $order->tableId()->getValue() === $tableUuid
                && $order->openedByUserId()->getValue() === $openedByUserUuid
                && $order->status()->isOpen()
        ));

        $tableRepository = Mockery::mock(TableRepositoryInterface::class);
        $tableRepository->shouldReceive('findById')->once()->with($tableUuid, $restaurantId)->andReturn($table);

        $cashShiftRepository = Mockery::mock(CashShiftRepositoryInterface::class);
        $cashShiftRepository->shouldReceive('findOpenByRestaurant')->once()->with($restaurantId)->andReturn(
            CashShift::open(Uuid::generate(), $restaurantId, Uuid::create($openedByUserUuid), 10000, null)
        );

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once()->with(Mockery::on(
            fn (ActionLogged $event) => $event->action === 'order.opened'
                && $event->userId === $openedByUserUuid
                && $event->data['opened_by_user_id'] === $openedByUserUuid
        ));

        $useCase = new OpenOrder(
            $orderRepository,
            $tableRepository,
            $cashShiftRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $useCase($auditContext, $tableUuid, $openedByUserUuid, 3);
    }

    public function test_open_order_rejects_merged_child_table(): void
    {
        $restaurantId = 1;
        $tableUuid = Uuid::generate()->getValue();
        $authenticatedUserUuid = Uuid::generate()->getValue();
        $openedByUserUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, $authenticatedUserUuid, '127.0.0.1');

        $table = Table::dddCreate(
            Uuid::create($tableUuid),
            TableName::create('Mesa hija'),
            Uuid::generate(),
            $restaurantId,
        );
        $table->mergeWith(Uuid::generate());

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldNotReceive('findOpenByTableId');
        $orderRepository->shouldNotReceive('save');

        $tableRepository = Mockery::mock(TableRepositoryInterface::class);
        $tableRepository->shouldReceive('findById')->once()->with($tableUuid, $restaurantId)->andReturn($table);

        $cashShiftRepository = Mockery::mock(CashShiftRepositoryInterface::class);
        $cashShiftRepository->shouldReceive('findOpenByRestaurant')->once()->with($restaurantId)->andReturn(
            CashShift::open(Uuid::generate(), $restaurantId, Uuid::create($openedByUserUuid), 10000, null)
        );

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldNotReceive('dispatch');

        $useCase = new OpenOrder(
            $orderRepository,
            $tableRepository,
            $cashShiftRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $this->expectException(CannotOpenOrderOnMergedTableException::class);
        $this->expectExceptionMessage('No se puede abrir un pedido en una mesa agrupada. Accede desde la mesa principal.');

        $useCase($auditContext, $tableUuid, $openedByUserUuid, 2);
    }

    private function transactionManager(): TransactionManagerInterface
    {
        return new class implements TransactionManagerInterface
        {
            public function run(callable $callback): mixed
            {
                return $callback();
            }
        };
    }
}
