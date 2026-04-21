<?php

declare(strict_types=1);

namespace App\Order\Application\SendOrderToKitchen;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;

class SendOrderToKitchen
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderLineRepositoryInterface $orderLineRepository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $orderUuid): void
    {
        $this->transactionManager->run(function () use ($auditContext, $orderUuid) {
            $order = $this->orderRepository->findById($orderUuid, $auditContext->restaurantId);
            if (!$order) {
                throw new OrderNotFoundException($orderUuid);
            }

            $lines = $this->orderLineRepository->findAllByOrderId($orderUuid, $auditContext->restaurantId);
            $pendingLines = array_filter($lines, static fn($line) => !$line->isSentToKitchen());

            if (empty($pendingLines)) {
                return;
            }

            foreach ($pendingLines as $line) {
                $line->markSentToKitchen();
                $this->orderLineRepository->update($line);
            }

            $order->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'order.sent_to_kitchen',
                'order',
                $orderUuid,
                ['lines_sent' => count($pendingLines)],
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$order->pullDomainEvents());
        });
    }
}