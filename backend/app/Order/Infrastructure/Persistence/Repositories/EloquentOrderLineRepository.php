<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Exception\OrderLinePersistenceRelationNotFoundException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        $orderId = $this->resolveOrderId($line->orderId()->getValue());
        $productId = $this->resolveProductId($line->productId()->getValue());
        $userId = $this->resolveUserId($line->userId()->getValue());

        $attributes = [
            'uuid' => $line->uuid()->getValue(),
            'restaurant_id' => $line->restaurantId(),
            'order_id' => $orderId,
            'product_id' => $productId,
            'user_id' => $userId,
            'quantity' => $line->quantity()->getValue(),
            'price' => $line->price(),
            'tax_percentage' => $line->taxPercentage(),
            'discount_type' => $line->discountType(),
            'discount_value' => $line->discountValue(),
            'discount_amount' => $line->discountAmount(),
            'sent_to_kitchen_at' => $line->sentToKitchenAt()?->format('Y-m-d H:i:s'),
            'paid_at' => $line->paidAt()?->format('Y-m-d H:i:s'),
        ];

        $this->model->newQuery()->create($attributes);
    }

    public function findById(string $uuid, int $restaurantId): ?OrderLine
    {
        $model = $this->newQueryWithRelations()
            ->where('uuid', $uuid)
            ->where('restaurant_id', $restaurantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAllByOrderId(string $orderUuid, int $restaurantId): array
    {
        $orderId = $this->resolveOrderId($orderUuid);

        return $this->newQueryWithRelations()
            ->where('order_id', $orderId)
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(fn (EloquentOrderLine $model) => $this->toDomain($model))
            ->toArray();
    }

    public function findAllByIdsForUpdate(array $lineUuids, string $orderUuid, int $restaurantId): array
    {
        if ($lineUuids === []) {
            return [];
        }

        $orderId = $this->resolveOrderId($orderUuid);

        return $this->newQueryWithRelations()
            ->where('restaurant_id', $restaurantId)
            ->where('order_id', $orderId)
            ->whereIn('uuid', $lineUuids)
            ->lockForUpdate()
            ->get()
            ->map(fn (EloquentOrderLine $model) => $this->toDomain($model))
            ->toArray();
    }

    public function findAllByOrderIds(array $orderUuids, int $restaurantId): array
    {
        if ($orderUuids === []) {
            return [];
        }

        $ordersByUuid = $this->orderModel->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('uuid', $orderUuids)
            ->pluck('id', 'uuid')
            ->all();

        if ($ordersByUuid === []) {
            return [];
        }

        $groupedLines = array_fill_keys(array_keys($ordersByUuid), []);

        foreach ($this->newQueryWithRelations()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('order_id', array_values($ordersByUuid))
            ->get() as $model) {
            $orderUuid = $this->resolveLoadedOrderUuid($model);
            $groupedLines[$orderUuid][] = $this->toDomain($model);
        }

        return $groupedLines;
    }

    public function bulkMarkSentToKitchen(array $lineUuids, int $restaurantId, DomainDateTime $sentAt): void
    {
        if ($lineUuids === []) {
            return;
        }

        $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('uuid', $lineUuids)
            ->whereNull('sent_to_kitchen_at')
            ->update([
                'sent_to_kitchen_at' => $sentAt->format('Y-m-d H:i:s'),
            ]);
    }

    public function update(OrderLine $line): void
    {
        $attributes = [
            'order_id' => $this->resolveOrderId($line->orderId()->getValue()),
            'quantity' => $line->quantity()->getValue(),
            'discount_type' => $line->discountType(),
            'discount_value' => $line->discountValue(),
            'discount_amount' => $line->discountAmount(),
            'sent_to_kitchen_at' => $line->sentToKitchenAt()?->format('Y-m-d H:i:s'),
            'paid_at' => $line->paidAt()?->format('Y-m-d H:i:s'),
        ];

        try {
            $this->model->newQuery()
                ->where('uuid', $line->uuid()->getValue())
                ->firstOrFail()
                ->update($attributes);
        } catch (ModelNotFoundException) {
            throw new OrderLineNotFoundException($line->uuid()->getValue());
        }
    }

    public function delete(string $uuid, int $restaurantId): void
    {
        try {
            $this->model->newQuery()
                ->where('uuid', $uuid)
                ->where('restaurant_id', $restaurantId)
                ->firstOrFail()
                ->delete();
        } catch (ModelNotFoundException) {
            throw new OrderLineNotFoundException($uuid);
        }
    }

    private function toDomain(EloquentOrderLine $model): OrderLine
    {
        $orderUuid = $this->resolveLoadedOrderUuid($model);
        $productUuid = $this->resolveLoadedProductUuid($model);
        $userUuid = $this->resolveLoadedUserUuid($model);

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
            $model->sent_to_kitchen_at
                ? $this->toDateTimeImmutable($model->sent_to_kitchen_at)
                : null,
            $model->paid_at
                ? $this->toDateTimeImmutable($model->paid_at)
                : null,
        );
    }

    private function toDateTimeImmutable(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        return new \DateTimeImmutable((string) $value);
    }

    private function newQueryWithRelations(): Builder
    {
        return $this->model->newQuery()->with(['order', 'product', 'user']);
    }

    private function resolveOrderId(string $orderUuid): int
    {
        try {
            return $this->orderModel->newQuery()->where('uuid', $orderUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new OrderNotFoundException($orderUuid);
        }
    }

    private function resolveProductId(string $productUuid): int
    {
        try {
            return $this->productModel->newQuery()->where('uuid', $productUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new ProductNotFoundException($productUuid);
        }
    }

    private function resolveUserId(string $userUuid): int
    {
        try {
            return $this->userModel->newQuery()->where('uuid', $userUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new UserNotFoundException($userUuid);
        }
    }

    private function resolveLoadedOrderUuid(EloquentOrderLine $model): string
    {
        if ($model->order === null) {
            throw OrderLinePersistenceRelationNotFoundException::missingOrder($model->uuid, (int) $model->order_id);
        }

        return $model->order->uuid;
    }

    private function resolveLoadedProductUuid(EloquentOrderLine $model): string
    {
        if ($model->product === null) {
            throw OrderLinePersistenceRelationNotFoundException::missingProduct($model->uuid, (int) $model->product_id);
        }

        return $model->product->uuid;
    }

    private function resolveLoadedUserUuid(EloquentOrderLine $model): string
    {
        if ($model->user === null) {
            throw OrderLinePersistenceRelationNotFoundException::missingUser($model->uuid, (int) $model->user_id);
        }

        return $model->user->uuid;
    }
}
