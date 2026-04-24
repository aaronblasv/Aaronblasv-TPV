<?php

declare(strict_types=1);

namespace App\Order\Application\VoidSentOrderLine;

use App\Order\Domain\Exception\CannotVoidOrderLineWithPaymentsException;
use App\Order\Domain\Exception\CannotVoidPendingOrderLineException;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Exception\OrderLineNotFoundInOrderContextException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Payment\Domain\Interfaces\PaymentRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;

class VoidSentOrderLine
{
    public function __construct(
        private OrderLineRepositoryInterface $lineRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $orderUuid, string $lineUuid): void
    {
        $this->transactionManager->run(function () use ($auditContext, $orderUuid, $lineUuid) {
            $line = $this->lineRepository->findById($lineUuid, $auditContext->restaurantId);

            if (! $line) {
                throw new OrderLineNotFoundException($lineUuid);
            }

            if ($line->orderId()->getValue() !== $orderUuid) {
                throw new OrderLineNotFoundInOrderContextException($lineUuid, $orderUuid);
            }

            if (! $line->isSentToKitchen()) {
                throw new CannotVoidPendingOrderLineException($lineUuid);
            }

            if ($this->paymentRepository->getTotalPaidByOrder($orderUuid) > 0) {
                throw new CannotVoidOrderLineWithPaymentsException($orderUuid);
            }

            $this->lineRepository->delete($lineUuid, $auditContext->restaurantId);

            $line->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'order.line.voided_after_kitchen',
                'order',
                $orderUuid,
                [
                    'line_uuid' => $lineUuid,
                    'product_id' => $line->productId()->getValue(),
                    'quantity' => $line->quantity()->getValue(),
                ],
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$line->pullDomainEvents());
        });
    }
}
