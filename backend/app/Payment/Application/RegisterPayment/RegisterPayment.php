<?php

declare(strict_types=1);

namespace App\Payment\Application\RegisterPayment;

use App\Order\Domain\Exception\OrderLineNotFoundInOrderContextException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Interfaces\PaymentRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class RegisterPayment
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
        private OrderLineRepositoryInterface $orderLineRepository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(
        AuditContext $auditContext,
        string $orderUuid,
        string $paidByUserUuid,
        int $amount,
        string $method,
        array $lineAllocations = [],
        ?string $description = null,
    ): RegisterPaymentResponse {
        return $this->transactionManager->run(function () use ($auditContext, $orderUuid, $paidByUserUuid, $amount, $method, $lineAllocations, $description) {
            $payment = Payment::dddCreate(
                Uuid::generate(),
                Uuid::create($orderUuid),
                Uuid::create($paidByUserUuid),
                $amount,
                $method,
                $description,
            );

            $this->repository->save($payment);

            $this->applyLineAllocations($payment->uuid(), $orderUuid, $auditContext->restaurantId, $lineAllocations);

            $totalPaid = $this->repository->getTotalPaidByOrder($orderUuid);
            $response = RegisterPaymentResponse::create($payment, $totalPaid);

            $payment->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $paidByUserUuid,
                'payment.registered',
                'order',
                $orderUuid,
                [
                    'amount' => $amount,
                    'method' => $method,
                    'allocated_lines' => $lineAllocations,
                    'total_paid' => $response->totalPaid,
                ],
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$payment->pullDomainEvents());

            return $response;
        });
    }

    private function applyLineAllocations(Uuid $paymentUuid, string $orderUuid, int $restaurantId, array $lineAllocations): void
    {
        if ($lineAllocations === []) {
            return;
        }

        $requestedQuantities = [];

        foreach ($lineAllocations as $allocation) {
            $lineUuid = (string) ($allocation['line_uuid'] ?? '');
            $quantity = (int) ($allocation['quantity'] ?? 0);

            if ($lineUuid === '' || $quantity <= 0) {
                continue;
            }

            $requestedQuantities[$lineUuid] = ($requestedQuantities[$lineUuid] ?? 0) + $quantity;
        }

        $lines = $this->orderLineRepository->findAllByIdsForUpdate(array_keys($requestedQuantities), $orderUuid, $restaurantId);
        $linesByUuid = [];

        foreach ($lines as $line) {
            $linesByUuid[$line->uuid()->getValue()] = $line;
        }

        foreach ($requestedQuantities as $lineUuid => $quantity) {
            $line = $linesByUuid[$lineUuid] ?? null;

            if ($line === null) {
                throw new OrderLineNotFoundInOrderContextException($lineUuid, $orderUuid);
            }

            [$paidLine, $remainingLine] = $line->splitOffPaidQuantity($quantity, Uuid::generate());

            if ($remainingLine !== null) {
                $this->orderLineRepository->update($remainingLine);
                $this->orderLineRepository->save($paidLine);

                continue;
            }

            $paidLine->markPaid();
            $this->orderLineRepository->update($paidLine);
        }
    }
}
