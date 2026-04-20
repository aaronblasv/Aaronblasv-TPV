<?php

declare(strict_types=1);

namespace App\Order\Application\GetAllOpenOrders;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;

class GetAllOpenOrders
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private OrderLineRepositoryInterface $lineRepository,
    ) {}

    public function __invoke(int $restaurantId): array
    {
        $orders = $this->repository->findAllOpen($restaurantId);

        return array_map(function ($order) use ($restaurantId) {
            $lines = $this->lineRepository->findAllByOrderId($order->uuid()->getValue(), $restaurantId);

            return GetAllOpenOrdersResponse::create($order, $lines);
        }, $orders);
    }
}
