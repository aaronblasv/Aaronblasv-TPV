<?php

declare(strict_types=1);

namespace App\Order\Application\RemoveOrderLine;

use App\Order\Domain\Exception\CannotModifyPaidOrderLineException;
use App\Order\Domain\Exception\CannotRemoveSentToKitchenOrderLineException;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Exception\OrderLineNotFoundInOrderContextException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;

class RemoveOrderLine
{
    public function __construct(
        private OrderLineRepositoryInterface $repository,
    ) {}

    public function __invoke(string $orderUuid, string $lineUuid, int $restaurantId): void
    {
        $line = $this->repository->findById($lineUuid, $restaurantId);
        if (! $line) {
            throw new OrderLineNotFoundException($lineUuid);
        }

        if ($line->orderId()->getValue() !== $orderUuid) {
            throw new OrderLineNotFoundInOrderContextException($lineUuid, $orderUuid);
        }

        if ($line->isPaid()) {
            throw new CannotModifyPaidOrderLineException($lineUuid);
        }

        if ($line->isSentToKitchen()) {
            throw new CannotRemoveSentToKitchenOrderLineException($lineUuid);
        }

        $this->repository->delete($lineUuid, $restaurantId);
    }
}
