<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderByTable;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Payment\Domain\Interfaces\PaymentRepositoryInterface;

class GetOrderByTable
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderLineRepositoryInterface $lineRepository,
        private PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function __invoke(string $tableUuid, int $restaurantId): ?GetOrderByTableResponse
    {
        $order = $this->orderRepository->findOpenByTableId($tableUuid, $restaurantId);
        if (!$order) {
            return null;
        }

        $lines = $this->lineRepository->findAllByOrderId($order->uuid()->getValue(), $restaurantId);
        $totalPaid = $this->paymentRepository->getTotalPaidByOrder($order->uuid()->getValue());

        return GetOrderByTableResponse::create($order, $lines, $totalPaid);
    }
}
