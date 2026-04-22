<?php

declare(strict_types=1);

namespace App\Order\Application\CloseOrder;

use App\Order\Domain\Event\OrderClosed;
use App\Order\Domain\Exception\CannotCloseOrderWithNoLinesException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleWriteRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class CloseOrder
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderLineRepositoryInterface $lineRepository,
        private SaleWriteRepositoryInterface $saleRepository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $orderUuid, string $closedByUserUuid): CloseOrderResponse
    {
        return $this->transactionManager->run(function () use ($auditContext, $orderUuid, $closedByUserUuid) {
            $order = $this->orderRepository->findById($orderUuid, $auditContext->restaurantId);
            if (!$order) {
                throw new OrderNotFoundException($orderUuid);
            }

            $lines = $this->lineRepository->findAllByOrderId($orderUuid, $auditContext->restaurantId);
            if (empty($lines)) {
                throw new CannotCloseOrderWithNoLinesException($orderUuid);
            }

            $subtotal = $order->calculateSubtotal($lines);
            $taxAmount = $order->calculateTaxAmount($lines);
            $lineDiscountTotal = $order->calculateLineDiscountTotal($lines);
            $orderDiscountTotal = $order->calculateOrderDiscountAmount($lines);
            $total = $subtotal + $taxAmount;
            $ticketNumber = $this->saleRepository->getNextTicketNumber($order->restaurantId());

            $order->close(Uuid::create($closedByUserUuid));
            $this->orderRepository->update($order);

            $order->recordDomainEvent(new OrderClosed(
                orderUuid: $order->uuid(),
                restaurantId: $order->restaurantId(),
                closedByUserUuid: Uuid::create($closedByUserUuid),
                ticketNumber: $ticketNumber,
                subtotal: $subtotal,
                taxAmount: $taxAmount,
                lineDiscountTotal: $lineDiscountTotal,
                orderDiscountTotal: $orderDiscountTotal,
                total: $total,
                lines: $lines,
            ));

            $order->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $closedByUserUuid,
                'order.closed',
                'order',
                $orderUuid,
                ['closed_by_user_id' => $closedByUserUuid],
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$order->pullDomainEvents());

            return CloseOrderResponse::create($order, $total, $ticketNumber);
        });
    }
}
