<?php

declare(strict_types=1);

namespace App\Order\Application\AddOrderLine;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Exception\CannotAddLinesToClosedOrderException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\Uuid;

class AddOrderLine
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderLineRepositoryInterface $lineRepository,
    ) {}

    public function __invoke(
        int $restaurantId,
        string $orderUuid,
        string $productUuid,
        string $userUuid,
        int $quantity,
        int $price,
        int $taxPercentage,
    ): AddOrderLineResponse {
        $order = $this->orderRepository->findById($orderUuid, $restaurantId);
        if (!$order) {
            throw new OrderNotFoundException($orderUuid);
        }
        if (!$order->status()->isOpen()) {
            throw new CannotAddLinesToClosedOrderException($orderUuid);
        }

        $line = OrderLine::dddCreate(
            Uuid::generate(),
            $restaurantId,
            Uuid::create($orderUuid),
            Uuid::create($productUuid),
            Uuid::create($userUuid),
            Quantity::create($quantity),
            $price,
            $taxPercentage,
        );

        $this->lineRepository->save($line);

        return AddOrderLineResponse::create($line);
    }
}