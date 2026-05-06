<?php

declare(strict_types=1);

namespace App\Order\Application\UpdateOrderLineDiscount;

use App\Order\Domain\Exception\CannotModifyPaidOrderLineException;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;

class UpdateOrderLineDiscount
{
    public function __construct(
        private OrderLineRepositoryInterface $orderLineRepository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $orderUuid, string $lineUuid, ?string $discountType, int $discountValue): void
    {
        $this->transactionManager->run(function () use ($auditContext, $orderUuid, $lineUuid, $discountType, $discountValue) {
            $line = $this->orderLineRepository->findById($lineUuid, $auditContext->restaurantId);
            if (!$line) {
                throw new OrderLineNotFoundException($lineUuid);
            }

            if ($line->isPaid()) {
                throw new CannotModifyPaidOrderLineException($lineUuid);
            }

            $line->applyDiscount($discountType, $discountValue);
            $this->orderLineRepository->update($line);

            $line->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'order.line.discount.updated',
                'order_line',
                $lineUuid,
                [
                    'order_uuid' => $orderUuid,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                ],
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$line->pullDomainEvents());
        });
    }
}