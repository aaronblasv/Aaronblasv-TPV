<?php

declare(strict_types=1);

namespace App\Order\Application\CancelOrder;

use App\Order\Domain\Exception\CannotCancelOrderException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;

class CancelOrder
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    public function __invoke(string $orderUuid, int $restaurantId): void
    {
        $order = $this->repository->findById($orderUuid, $restaurantId);
        if (!$order) {
            throw new OrderNotFoundException($orderUuid);
        }
        if (!$order->status()->isOpen()) {
            throw new CannotCancelOrderException($orderUuid);
        }

        $this->repository->delete($orderUuid, $restaurantId);
    }
}
