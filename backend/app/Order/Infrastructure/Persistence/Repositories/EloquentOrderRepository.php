<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private EloquentOrder $model,
        private EloquentTable $tableModel,
        private EloquentUser $userModel,
    ) {}

    public function save(Order $order): void
    {
        $tableId = $this->tableModel->newQuery()->where('uuid', $order->tableId()->getValue())->firstOrFail()->id;
        $openedByUserId = $this->userModel->newQuery()->where('uuid', $order->openedByUserId()->getValue())->firstOrFail()->id;

        $this->model->newQuery()->create([
            'uuid'              => $order->uuid()->getValue(),
            'restaurant_id'     => $order->restaurantId(),
            'status'            => $order->status()->getValue(),
            'table_id'          => $tableId,
            'opened_by_user_id' => $openedByUserId,
            'closed_by_user_id' => null,
            'diners'            => $order->diners()->getValue(),
            'discount_type'     => $order->discountType(),
            'discount_value'    => $order->discountValue(),
            'discount_amount'   => $order->discountAmount(),
            'opened_at'         => $order->openedAt()->format('Y-m-d H:i:s'),
            'closed_at'         => null,
        ]);
    }

    public function findById(string $uuid, int $restaurantId): ?Order
    {
        $model = $this->model->newQuery()
            ->with(['table', 'openedByUser', 'closedByUser'])
            ->where('uuid', $uuid)
            ->where('restaurant_id', $restaurantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findOpenByTableId(string $tableUuid, int $restaurantId): ?Order
    {
        $table = $this->tableModel->newQuery()->where('uuid', $tableUuid)->first();
        if (!$table) {
            return null;
        }

        $model = $this->model->newQuery()
            ->where('table_id', $table->id)
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'open')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function update(Order $order): void
    {
        $model = $this->model->newQuery()->where('uuid', $order->uuid()->getValue())->firstOrFail();

        $tableId = $this->tableModel->newQuery()
            ->where('uuid', $order->tableId()->getValue())
            ->firstOrFail()
            ->id;

        $data = [
            'table_id' => $tableId,
            'status' => $order->status()->getValue(),
            'diners' => $order->diners()->getValue(),
            'discount_type' => $order->discountType(),
            'discount_value' => $order->discountValue(),
            'discount_amount' => $order->discountAmount(),
        ];

        if ($order->closedByUserId()) {
            $data['closed_by_user_id'] = $this->userModel->newQuery()
                ->where('uuid', $order->closedByUserId()->getValue())
                ->firstOrFail()->id;
            $data['closed_at'] = $order->closedAt()->format('Y-m-d H:i:s');
        }

        $model->update($data);
    }

    public function delete(string $uuid, int $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $uuid)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail()
            ->delete();
    }

    public function findAllOpen(int $restaurantId): array
    {
        return $this->model->newQuery()
            ->with(['table', 'openedByUser', 'closedByUser'])
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'open')
            ->get()
            ->map(fn(EloquentOrder $model) => $this->toDomain($model))
            ->toArray();
    }

    private function toDomain(EloquentOrder $model): Order
    {
        $tableUuid = $model->relationLoaded('table')
            ? $model->table->uuid
            : $this->tableModel->newQuery()->find($model->table_id)->uuid;

        $openedByUuid = $model->relationLoaded('openedByUser')
            ? $model->openedByUser->uuid
            : $this->userModel->newQuery()->find($model->opened_by_user_id)->uuid;

        $closedByUuid = $model->closed_by_user_id
            ? ($model->relationLoaded('closedByUser')
                ? $model->closedByUser?->uuid
                : $this->userModel->newQuery()->find($model->closed_by_user_id)->uuid)
            : null;

        return Order::fromPersistence(
            $model->uuid,
            $model->restaurant_id,
            $model->status,
            $tableUuid,
            $openedByUuid,
            $closedByUuid,
            $model->diners,
            $model->discount_type,
            (int) $model->discount_value,
            (int) $model->discount_amount,
            new \DateTimeImmutable($model->opened_at),
            $model->closed_at ? new \DateTimeImmutable($model->closed_at) : null,
        );
    }
}
