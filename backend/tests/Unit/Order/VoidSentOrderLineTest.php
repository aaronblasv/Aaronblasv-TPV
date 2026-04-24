<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\VoidSentOrderLine\VoidSentOrderLine;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Exception\CannotVoidOrderLineWithPaymentsException;
use App\Order\Domain\Exception\CannotVoidPendingOrderLineException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\ValueObject\Quantity;
use App\Payment\Domain\Interfaces\PaymentRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class VoidSentOrderLineTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_voids_a_sent_line_and_dispatches_audit_log(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $lineUuid = Uuid::generate()->getValue();
        $productUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, Uuid::generate()->getValue(), '127.0.0.1');

        $line = $this->makeLine($orderUuid, $productUuid, 2, true);

        $lineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $lineRepository->shouldReceive('findById')->once()->with($lineUuid, $restaurantId)->andReturn($line);
        $lineRepository->shouldReceive('delete')->once()->with($lineUuid, $restaurantId);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('getTotalPaidByOrder')->once()->with($orderUuid)->andReturn(0);

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once()->withArgs(function (...$events) use ($orderUuid, $lineUuid, $productUuid) {
            foreach ($events as $event) {
                if ($event instanceof ActionLogged) {
                    return $event->action === 'order.line.voided_after_kitchen'
                        && $event->entityUuid === $orderUuid
                        && $event->data === [
                            'line_uuid' => $lineUuid,
                            'product_id' => $productUuid,
                            'quantity' => 2,
                        ];
                }
            }

            return false;
        });

        $useCase = new VoidSentOrderLine(
            $lineRepository,
            $paymentRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $useCase($auditContext, $orderUuid, $lineUuid);
    }

    public function test_it_rejects_voiding_a_pending_line(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $lineUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, Uuid::generate()->getValue(), '127.0.0.1');

        $line = $this->makeLine($orderUuid, Uuid::generate()->getValue(), 1, false);

        $lineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $lineRepository->shouldReceive('findById')->once()->with($lineUuid, $restaurantId)->andReturn($line);
        $lineRepository->shouldNotReceive('delete');

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldNotReceive('getTotalPaidByOrder');

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldNotReceive('dispatch');

        $useCase = new VoidSentOrderLine(
            $lineRepository,
            $paymentRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $this->expectException(CannotVoidPendingOrderLineException::class);

        $useCase($auditContext, $orderUuid, $lineUuid);
    }

    public function test_it_rejects_voiding_a_sent_line_when_payments_exist(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $lineUuid = Uuid::generate()->getValue();
        $productUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, Uuid::generate()->getValue(), '127.0.0.1');

        $line = $this->makeLine($orderUuid, $productUuid, 1, true);

        $lineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $lineRepository->shouldReceive('findById')->once()->with($lineUuid, $restaurantId)->andReturn($line);
        $lineRepository->shouldNotReceive('delete');

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('getTotalPaidByOrder')->once()->with($orderUuid)->andReturn(500);

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldNotReceive('dispatch');

        $useCase = new VoidSentOrderLine(
            $lineRepository,
            $paymentRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $this->expectException(CannotVoidOrderLineWithPaymentsException::class);

        $useCase($auditContext, $orderUuid, $lineUuid);
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

    private function makeLine(string $orderUuid, string $productUuid, int $quantity, bool $sentToKitchen): OrderLine
    {
        return OrderLine::dddCreate(
            Uuid::generate(),
            1,
            Uuid::create($orderUuid),
            Uuid::create($productUuid),
            Uuid::generate(),
            Quantity::create($quantity),
            1200,
            10,
            null,
            0,
            0,
            $sentToKitchen ? DomainDateTime::now() : null,
        );
    }
}
