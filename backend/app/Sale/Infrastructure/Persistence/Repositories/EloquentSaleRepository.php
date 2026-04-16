<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Support\Facades\DB;

class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function __construct(
        private EloquentSale $model,
        private EloquentSaleLine $saleLineModel,
        private EloquentOrder $orderModel,
        private EloquentOrderLine $orderLineModel,
        private EloquentUser $userModel,
    ) {}

    public function save(Sale $sale): void
    {
        $orderId = $this->orderModel->newQuery()->where('uuid', $sale->orderId()->getValue())->firstOrFail()->id;
        $userId = $this->userModel->newQuery()->where('uuid', $sale->userId()->getValue())->firstOrFail()->id;

        $this->model->newQuery()->create([
            'uuid'          => $sale->uuid()->getValue(),
            'restaurant_id' => $sale->restaurantId(),
            'order_id'      => $orderId,
            'user_id'       => $userId,
            'ticket_number' => $sale->ticketNumber(),
            'value_date'    => $sale->valueDate()->format('Y-m-d H:i:s'),
            'total'         => $sale->total(),
        ]);
    }

    public function saveLine(SaleLine $line): void
    {
        $saleId = $this->model->newQuery()->where('uuid', $line->saleId()->getValue())->firstOrFail()->id;
        $orderLineId = $this->orderLineModel->newQuery()->where('uuid', $line->orderLineId()->getValue())->firstOrFail()->id;
        $userId = $this->userModel->newQuery()->where('uuid', $line->userId()->getValue())->firstOrFail()->id;

        $this->saleLineModel->newQuery()->create([
            'uuid'           => $line->uuid()->getValue(),
            'restaurant_id'  => $line->restaurantId(),
            'sale_id'        => $saleId,
            'order_line_id'  => $orderLineId,
            'user_id'        => $userId,
            'quantity'       => $line->quantity(),
            'price'          => $line->price(),
            'tax_percentage' => $line->taxPercentage(),
        ]);
    }

    public function getNextTicketNumber(int $restaurantId): int
    {
        $last = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->max('ticket_number');

        return ($last ?? 0) + 1;
    }

    public function findAll(int $restaurantId): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(EloquentSale $model) => $this->toDomain($model))
            ->toArray();
    }

    public function findFiltered(int $restaurantId, ?string $from, ?string $to): array
    {
        $query = DB::table('sales as s')
            ->join('orders as o', 's.order_id', '=', 'o.id')
            ->join('tables as t', 'o.table_id', '=', 't.id')
            ->join('users as open_user', 'o.opened_by_user_id', '=', 'open_user.id')
            ->leftJoin('users as close_user', 'o.closed_by_user_id', '=', 'close_user.id')
            ->where('s.restaurant_id', $restaurantId)
            ->whereNull('s.deleted_at')
            ->orderBy('s.value_date', 'desc')
            ->select(
                's.uuid',
                's.ticket_number',
                's.value_date',
                's.total',
                't.name as table_name',
                'open_user.name as open_user_name',
                'close_user.name as close_user_name',
                'o.opened_at',
                'o.closed_at',
            );

        if ($from !== null) {
            $query->where('s.value_date', '>=', $from . ' 00:00:00');
        }
        if ($to !== null) {
            $query->where('s.value_date', '<=', $to . ' 23:59:59');
        }

        return $query->get()->map(fn($row) => [
            'uuid'            => $row->uuid,
            'ticket_number'   => $row->ticket_number,
            'value_date'      => $row->value_date,
            'total'           => (int) $row->total,
            'table_name'      => $row->table_name,
            'open_user_name'  => $row->open_user_name,
            'close_user_name' => $row->close_user_name ?? '—',
            'opened_at'       => $row->opened_at,
            'closed_at'       => $row->closed_at,
        ])->all();
    }

    public function findLinesBySaleUuid(int $restaurantId, string $saleUuid): array
    {
        $saleId = DB::table('sales')
            ->where('uuid', $saleUuid)
            ->where('restaurant_id', $restaurantId)
            ->value('id');

        if ($saleId === null) {
            return [];
        }

        return DB::table('sales_lines as sl')
            ->join('order_lines as ol', 'sl.order_line_id', '=', 'ol.id')
            ->join('products as p', 'ol.product_id', '=', 'p.id')
            ->where('sl.sale_id', $saleId)
            ->whereNull('sl.deleted_at')
            ->select(
                'sl.uuid',
                'p.name as product_name',
                'sl.quantity',
                'sl.price',
                'sl.tax_percentage',
            )
            ->get()
            ->map(fn($row) => [
                'uuid'           => $row->uuid,
                'product_name'   => $row->product_name,
                'quantity'       => (int) $row->quantity,
                'price'          => (int) $row->price,
                'tax_percentage' => (int) $row->tax_percentage,
            ])
            ->all();
    }

    public function getGroupedReport(int $restaurantId, ?string $from, ?string $to): array
    {
        $applyFilters = function ($q) use ($restaurantId, $from, $to) {
            $q->where('s.restaurant_id', $restaurantId)->whereNull('s.deleted_at');
            if ($from !== null) $q->where('s.value_date', '>=', $from . ' 00:00:00');
            if ($to !== null)   $q->where('s.value_date', '<=', $to . ' 23:59:59');
            return $q;
        };

        $byDay = $applyFilters(DB::table('sales as s'))
            ->select(DB::raw('DATE(s.value_date) as day'), DB::raw('COUNT(*) as count'), DB::raw('SUM(s.total) as total'))
            ->groupBy('day')->orderBy('day')
            ->get()->map(fn($r) => ['day' => $r->day, 'count' => (int) $r->count, 'total' => (int) $r->total])->all();

        $byZone = $applyFilters(DB::table('sales as s'))
            ->join('orders as o', 's.order_id', '=', 'o.id')
            ->join('tables as t', 'o.table_id', '=', 't.id')
            ->join('zones as z', 't.zone_id', '=', 'z.id')
            ->select('z.name as zone_name', DB::raw('COUNT(*) as count'), DB::raw('SUM(s.total) as total'))
            ->groupBy('z.id', 'z.name')->orderByDesc('total')
            ->get()->map(fn($r) => ['zone_name' => $r->zone_name, 'count' => (int) $r->count, 'total' => (int) $r->total])->all();

        $byProduct = $applyFilters(DB::table('sales as s'))
            ->join('sales_lines as sl', 'sl.sale_id', '=', 's.id')
            ->join('order_lines as ol', 'sl.order_line_id', '=', 'ol.id')
            ->join('products as p', 'ol.product_id', '=', 'p.id')
            ->whereNull('sl.deleted_at')
            ->select('p.name as product_name', DB::raw('SUM(sl.quantity) as total_quantity'), DB::raw('SUM(sl.quantity * sl.price) as total'))
            ->groupBy('p.id', 'p.name')->orderByDesc('total_quantity')
            ->get()->map(fn($r) => ['product_name' => $r->product_name, 'total_quantity' => (int) $r->total_quantity, 'total' => (int) $r->total])->all();

        $byUser = $applyFilters(DB::table('sales as s'))
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select('u.name as user_name', DB::raw('COUNT(*) as count'), DB::raw('SUM(s.total) as total'))
            ->groupBy('u.id', 'u.name')->orderByDesc('total')
            ->get()->map(fn($r) => ['user_name' => $r->user_name, 'count' => (int) $r->count, 'total' => (int) $r->total])->all();

        return [
            'by_day'     => $byDay,
            'by_zone'    => $byZone,
            'by_product' => $byProduct,
            'by_user'    => $byUser,
        ];
    }

    private function toDomain(EloquentSale $model): Sale
    {
        $orderUuid = $this->orderModel->newQuery()->find($model->order_id)->uuid;
        $userUuid  = $this->userModel->newQuery()->find($model->user_id)->uuid;

        return Sale::fromPersistence(
            $model->uuid,
            $model->restaurant_id,
            $orderUuid,
            $userUuid,
            $model->ticket_number,
            new \DateTimeImmutable($model->value_date),
            $model->total,
        );
    }
}
