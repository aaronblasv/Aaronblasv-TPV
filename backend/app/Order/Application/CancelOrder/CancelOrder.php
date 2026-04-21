<?php

declare(strict_types=1);

namespace App\Order\Application\CancelOrder;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;

class CancelOrder
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $orderUuid): void
    {
        $this->transactionManager->run(function () use ($auditContext, $orderUuid) {
            $order = $this->repository->findById($orderUuid, $auditContext->restaurantId);
            if (!$order) {
                throw new OrderNotFoundException($orderUuid);
            }

            $order->cancel();
            $this->repository->update($order);

            $order->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'order.cancelled',
                'order',
                $orderUuid,
                null,
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$order->pullDomainEvents());
        });
    }
}
