<?php

declare(strict_types=1);

namespace App\Order\Application\UpdateOrderDiners;

use App\Order\Domain\Exception\CannotUpdateDinersOnClosedOrderException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\Diners;

class UpdateOrderDiners
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    public function __invoke(string $orderUuid, int $diners, int $restaurantId): void
    {
        $order = $this->repository->findById($orderUuid, $restaurantId);
        if (!$order) {
            throw new OrderNotFoundException($orderUuid);
        }
        if (!$order->status()->isOpen()) {
            throw new CannotUpdateDinersOnClosedOrderException($orderUuid);
        }

        $order->updateDiners(Diners::create($diners));
        $this->repository->update($order);
    }
}
