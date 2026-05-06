<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\ValueObject\Quantity;
use App\Payment\Application\RegisterPayment\RegisterPayment;
use App\Payment\Domain\Interfaces\PaymentRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RegisterPaymentTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_splits_allocated_lines_and_returns_updated_total_paid(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $paidByUserUuid = Uuid::generate()->getValue();
        $lineUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, $paidByUserUuid, '127.0.0.1');

        $line = OrderLine::dddCreate(
            Uuid::create($lineUuid),
            $restaurantId,
            Uuid::create($orderUuid),
            Uuid::generate(),
            Uuid::generate(),
            Quantity::create(3),
            1000,
            10,
        );

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('save')->once()->with(Mockery::on(
            fn ($payment) => $payment->orderId()->getValue() === $orderUuid
                && $payment->userId()->getValue() === $paidByUserUuid
                && $payment->amount() === 2200
                && $payment->method() === 'card'
                && $payment->description() === 'Pago parcial'
        ));
        $paymentRepository->shouldReceive('getTotalPaidByOrder')->once()->with($orderUuid)->andReturn(3700);

        $orderLineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $orderLineRepository->shouldReceive('findAllByIdsForUpdate')
            ->once()
            ->with([$lineUuid], $orderUuid, $restaurantId)
            ->andReturn([$line]);
        $orderLineRepository->shouldReceive('update')->once()->with(Mockery::on(
            fn (OrderLine $remainingLine) => $remainingLine->uuid()->getValue() === $lineUuid
                && $remainingLine->quantity()->getValue() === 1
                && $remainingLine->isPaid() === false
        ));
        $orderLineRepository->shouldReceive('save')->once()->with(Mockery::on(
            fn (OrderLine $paidLine) => $paidLine->uuid()->getValue() !== $lineUuid
                && $paidLine->orderId()->getValue() === $orderUuid
                && $paidLine->quantity()->getValue() === 2
                && $paidLine->isPaid() === true
        ));

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once()->withArgs(function (...$events) use ($orderUuid) {
            foreach ($events as $event) {
                if ($event instanceof ActionLogged) {
                    return $event->action === 'payment.registered'
                        && $event->entityUuid === $orderUuid
                        && $event->data['total_paid'] === 3700
                        && $event->data['allocated_lines'] === [['line_uuid' => $orderUuid !== '' ? $event->data['allocated_lines'][0]['line_uuid'] : '', 'quantity' => 2]];
                }
            }

            return false;
        });

        $useCase = new RegisterPayment(
            $paymentRepository,
            $orderLineRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $response = $useCase(
            $auditContext,
            $orderUuid,
            $paidByUserUuid,
            2200,
            'card',
            [['line_uuid' => $lineUuid, 'quantity' => 2]],
            'Pago parcial',
        );

        $this->assertSame($orderUuid, $response->orderUuid);
        $this->assertSame(2200, $response->amount);
        $this->assertSame('card', $response->method);
        $this->assertSame(3700, $response->totalPaid);
        $this->assertSame('Pago parcial', $response->description);
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