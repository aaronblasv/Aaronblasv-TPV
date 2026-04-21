<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleReadRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;

class EloquentSaleReadRepository implements SaleReadRepositoryInterface
{
    public function __construct(
        private EloquentSale $model,
        private EloquentSaleLine $saleLineModel,
    ) {}

    public function findAll(int $restaurantId): array
    {
        return $this->model->newQuery()
            ->join('orders', 'sales.order_id', '=', 'orders.id')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->where('sales.restaurant_id', $restaurantId)
            ->whereNull('sales.deleted_at')
            ->orderBy('sales.created_at', 'desc')
            ->select('sales.*', 'orders.uuid as order_uuid', 'users.uuid as user_uuid')
            ->get()
            ->map(fn($model) => Sale::fromPersistence(
                $model->uuid,
                $model->restaurant_id,
                $model->order_uuid,
                $model->user_uuid,
                $model->ticket_number,
                $this->toDateTimeImmutable($model->value_date),
                (int) $model->subtotal,
                (int) $model->tax_amount,
                (int) $model->line_discount_total,
                (int) $model->order_discount_total,
                $model->total,
                (int) $model->refunded_total,
            ))
            ->toArray();
    }

    public function findByUuid(int $restaurantId, string $saleUuid): ?Sale
    {
        $model = $this->model->newQuery()
            ->join('orders', 'sales.order_id', '=', 'orders.id')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->where('sales.restaurant_id', $restaurantId)
            ->where('sales.uuid', $saleUuid)
            ->select('sales.*', 'orders.uuid as order_uuid', 'users.uuid as user_uuid')
            ->first();

        if (!$model) {
            return null;
        }

        return Sale::fromPersistence(
            $model->uuid,
            $model->restaurant_id,
            $model->order_uuid,
            $model->user_uuid,
            $model->ticket_number,
            $this->toDateTimeImmutable($model->value_date),
            (int) $model->subtotal,
            (int) $model->tax_amount,
            (int) $model->line_discount_total,
            (int) $model->order_discount_total,
            $model->total,
            (int) $model->refunded_total,
        );
    }

    public function findDomainLinesBySaleUuid(int $restaurantId, string $saleUuid): array
    {
        $sale = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->where('uuid', $saleUuid)
            ->first();

        if (!$sale) {
            return [];
        }

        return $this->saleLineModel->newQuery()
            ->join('sales', 'sales_lines.sale_id', '=', 'sales.id')
            ->join('order_lines', 'sales_lines.order_line_id', '=', 'order_lines.id')
            ->join('users', 'sales_lines.user_id', '=', 'users.id')
            ->where('sales_lines.sale_id', $sale->id)
            ->where('sales_lines.restaurant_id', $restaurantId)
            ->select(
                'sales_lines.*',
                'sales.uuid as sale_uuid',
                'order_lines.uuid as order_line_uuid',
                'users.uuid as user_uuid',
            )
            ->get()
            ->map(fn($model) => SaleLine::fromPersistence(
                $model->uuid,
                $model->restaurant_id,
                $model->sale_uuid,
                $model->order_line_uuid,
                $model->user_uuid,
                (int) $model->quantity,
                (int) $model->price,
                (int) $model->tax_percentage,
                (int) $model->line_subtotal,
                (int) $model->tax_amount,
                $model->discount_type,
                (int) $model->discount_value,
                (int) $model->discount_amount,
                (int) $model->line_total,
                (int) $model->refunded_quantity,
            ))
            ->toArray();
    }

    private function toDateTimeImmutable(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        return new \DateTimeImmutable((string) $value);
    }
}