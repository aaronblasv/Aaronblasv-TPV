<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Exception\OrderLinePersistenceRelationNotFoundException;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Support\Facades\Schema;

class EloquentOrderLineRepository implements OrderLineRepositoryInterface
{
    private ?bool $hasSentToKitchenAtColumn = null;

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

        $attributes = [
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
        ];

        if ($this->supportsSentToKitchenAtColumn()) {
            $attributes['sent_to_kitchen_at'] = $line->sentToKitchenAt()?->format('Y-m-d H:i:s');
        }

        $this->model->newQuery()->create($attributes);
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
        $attributes = [
            'order_id' => $this->orderModel->newQuery()->where('uuid', $line->orderId()->getValue())->firstOrFail()->id,
            'quantity' => $line->quantity()->getValue(),
            'discount_type' => $line->discountType(),
            'discount_value' => $line->discountValue(),
            'discount_amount' => $line->discountAmount(),
        ];

        if ($this->supportsSentToKitchenAtColumn()) {
            $attributes['sent_to_kitchen_at'] = $line->sentToKitchenAt()?->format('Y-m-d H:i:s');
        }

        $this->model->newQuery()
            ->where('uuid', $line->uuid()->getValue())
            ->firstOrFail()
            ->update($attributes);
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
        $orderUuid = $this->resolveOrderUuid($model);
        $productUuid = $this->resolveProductUuid($model);
        $userUuid = $this->resolveUserUuid($model);

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
            $this->supportsSentToKitchenAtColumn() && $model->sent_to_kitchen_at
                ? new \DateTimeImmutable($model->sent_to_kitchen_at)
                : null,
        );
    }

    private function supportsSentToKitchenAtColumn(): bool
    {
        return $this->hasSentToKitchenAtColumn ??= Schema::hasColumn($this->model->getTable(), 'sent_to_kitchen_at');
    }

    private function resolveOrderUuid(EloquentOrderLine $model): string
    {
        if ($model->relationLoaded('order')) {
            if ($model->order === null) {
                throw OrderLinePersistenceRelationNotFoundException::missingOrder($model->uuid, (int) $model->order_id);
            }

            return $model->order->uuid;
        }

        $order = $this->orderModel->newQuery()->find($model->order_id);
        if ($order === null) {
            throw OrderLinePersistenceRelationNotFoundException::missingOrder($model->uuid, (int) $model->order_id);
        }

        return $order->uuid;
    }

    private function resolveProductUuid(EloquentOrderLine $model): string
    {
        if ($model->relationLoaded('product')) {
            if ($model->product === null) {
                throw OrderLinePersistenceRelationNotFoundException::missingProduct($model->uuid, (int) $model->product_id);
            }

            return $model->product->uuid;
        }

        $product = $this->productModel->newQuery()->find($model->product_id);
        if ($product === null) {
            throw OrderLinePersistenceRelationNotFoundException::missingProduct($model->uuid, (int) $model->product_id);
        }

        return $product->uuid;
    }

    private function resolveUserUuid(EloquentOrderLine $model): string
    {
        if ($model->relationLoaded('user')) {
            if ($model->user === null) {
                throw OrderLinePersistenceRelationNotFoundException::missingUser($model->uuid, (int) $model->user_id);
            }

            return $model->user->uuid;
        }

        $user = $this->userModel->newQuery()->find($model->user_id);
        if ($user === null) {
            throw OrderLinePersistenceRelationNotFoundException::missingUser($model->uuid, (int) $model->user_id);
        }

        return $user->uuid;
    }
}
