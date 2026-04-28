<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\ServiceWindow;
use App\Order\Domain\Entity\ServiceWindowLine;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\ServiceWindowRepositoryInterface;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Order\Infrastructure\Persistence\Models\EloquentServiceWindow;
use App\Order\Infrastructure\Persistence\Models\EloquentServiceWindowLine;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentServiceWindowRepository implements ServiceWindowRepositoryInterface
{
    public function __construct(
        private EloquentServiceWindow $model,
        private EloquentServiceWindowLine $lineModel,
        private EloquentOrder $orderModel,
        private EloquentOrderLine $orderLineModel,
        private EloquentUser $userModel,
    ) {}

    public function nextWindowNumberForOrder(string $orderUuid, int $restaurantId): int
    {
        $orderId = $this->resolveOrderId($orderUuid, $restaurantId);

        $currentMax = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->where('order_id', $orderId)
            ->lockForUpdate()
            ->max('window_number');

        return ((int) $currentMax) + 1;
    }

    public function save(ServiceWindow $serviceWindow): void
    {
        $orderId = $this->resolveOrderId($serviceWindow->orderId()->getValue(), $serviceWindow->restaurantId());
        $userId = $this->resolveUserId($serviceWindow->sentByUserId()->getValue(), $serviceWindow->restaurantId());

        $window = $this->model->newQuery()->create([
            'uuid' => $serviceWindow->uuid()->getValue(),
            'restaurant_id' => $serviceWindow->restaurantId(),
            'order_id' => $orderId,
            'sent_by_user_id' => $userId,
            'sent_by_user_name' => $serviceWindow->sentByUserName(),
            'window_number' => $serviceWindow->windowNumber(),
            'sent_at' => $serviceWindow->sentAt()->format('Y-m-d H:i:s'),
        ]);

        $orderLineIds = $this->orderLineModel->newQuery()
            ->where('restaurant_id', $serviceWindow->restaurantId())
            ->whereIn('uuid', array_map(fn (ServiceWindowLine $line): string => $line->orderLineId()->getValue(), $serviceWindow->lines()))
            ->pluck('id', 'uuid')
            ->all();

        $timestamp = now();
        $rows = [];

        foreach ($serviceWindow->lines() as $line) {
            $orderLineUuid = $line->orderLineId()->getValue();
            if (!isset($orderLineIds[$orderLineUuid])) {
                throw new OrderLineNotFoundException($orderLineUuid);
            }

            $rows[] = [
                'uuid' => $line->uuid()->getValue(),
                'restaurant_id' => $line->restaurantId(),
                'order_service_window_id' => $window->id,
                'order_line_id' => $orderLineIds[$orderLineUuid],
                'product_name' => $line->productName(),
                'quantity' => $line->quantity(),
                'price' => $line->price(),
                'tax_percentage' => $line->taxPercentage(),
                'discount_type' => $line->discountType(),
                'discount_value' => $line->discountValue(),
                'discount_amount' => $line->discountAmount(),
                'line_subtotal' => $line->lineSubtotal(),
                'tax_amount' => $line->taxAmount(),
                'line_total' => $line->lineTotal(),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($rows !== []) {
            $this->lineModel->newQuery()->insert($rows);
        }
    }

    private function resolveOrderId(string $orderUuid, int $restaurantId): int
    {
        try {
            return $this->orderModel->newQuery()
                ->where('uuid', $orderUuid)
                ->where('restaurant_id', $restaurantId)
                ->firstOrFail()
                ->id;
        } catch (ModelNotFoundException) {
            throw new OrderNotFoundException($orderUuid);
        }
    }

    private function resolveUserId(string $userUuid, int $restaurantId): int
    {
        try {
            return $this->userModel->newQuery()
                ->where('uuid', $userUuid)
                ->where('restaurant_id', $restaurantId)
                ->firstOrFail()
                ->id;
        } catch (ModelNotFoundException) {
            throw new UserNotFoundException($userUuid);
        }
    }
}
