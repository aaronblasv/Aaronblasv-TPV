<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

class EloquentOrderLineRepository implements OrderLineRepositoryInterface
{
    public function __construct(
        private EloquentOrderLine $model,
        private EloquentOrder $orderModel,
        private EloquentProduct $productModel,
        private EloquentUser $userModel,
    ) {}

    public function save(OrderLine $line): void
    {
        $orderId = $this->orderModel->newQuery()->where('uuid', $line->orderId()->getValue())->firstOrFail()->id;
        $productId = $this->productModel->newQuery()->where('uuid', $line->productId()->getValue())->firstOrFail()->id;
        $userId = $this->userModel->newQuery()->where('uuid', $line->userId()->getValue())->firstOrFail()->id;

        $this->model->newQuery()->create([
            'uuid'           => $line->uuid()->getValue(),
            'restaurant_id'  => $line->restaurantId(),
            'order_id'       => $orderId,
            'product_id'     => $productId,
            'user_id'        => $userId,
            'quantity'       => $line->quantity()->getValue(),
            'price'          => $line->price(),
            'tax_percentage' => $line->taxPercentage(),
            'discount_type'  => $line->discountType(),
            'discount_value' => $line->discountValue(),
            'discount_amount' => $line->discountAmount(),
        ]);
    }

    public function findById(string $uuid, int $restaurantId): ?OrderLine
    {
        $model = $this->model->newQuery()
            ->with(['order', 'product', 'user'])
            ->where('uuid', $uuid)
            ->where('restaurant_id', $restaurantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAllByOrderId(string $orderUuid, int $restaurantId): array
    {
        $order = $this->orderModel->newQuery()->where('uuid', $orderUuid)->firstOrFail();

        return $this->model->newQuery()
            ->with(['order', 'product', 'user'])
            ->where('order_id', $order->id)
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(fn(EloquentOrderLine $model) => $this->toDomain($model))
            ->toArray();
    }

    public function update(OrderLine $line): void
    {
        $this->model->newQuery()
            ->where('uuid', $line->uuid()->getValue())
            ->firstOrFail()
            ->update([
                'quantity' => $line->quantity()->getValue(),
                'discount_type' => $line->discountType(),
                'discount_value' => $line->discountValue(),
                'discount_amount' => $line->discountAmount(),
            ]);
    }

    public function delete(string $uuid, int $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $uuid)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail()
            ->delete();
    }

    private function toDomain(EloquentOrderLine $model): OrderLine
    {
        $orderUuid = $model->relationLoaded('order')
            ? $model->order->uuid
            : $this->orderModel->newQuery()->find($model->order_id)->uuid;
        $productUuid = $model->relationLoaded('product')
            ? $model->product->uuid
            : $this->productModel->newQuery()->find($model->product_id)->uuid;
        $userUuid = $model->relationLoaded('user')
            ? $model->user->uuid
            : $this->userModel->newQuery()->find($model->user_id)->uuid;

        return OrderLine::fromPersistence(
            $model->uuid,
            $model->restaurant_id,
            $orderUuid,
            $productUuid,
            $userUuid,
            $model->quantity,
            $model->price,
            $model->tax_percentage,
            $model->discount_type,
            (int) $model->discount_value,
            (int) $model->discount_amount,
        );
    }
}
