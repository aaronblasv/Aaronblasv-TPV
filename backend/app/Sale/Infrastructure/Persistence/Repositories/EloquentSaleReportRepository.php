<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Interfaces\SaleReportRepositoryInterface;
use App\Sale\Domain\ReadModel\SaleLineDetail;
use App\Sale\Domain\ReadModel\SalesGroupedReport;
use App\Sale\Domain\ReadModel\SalesReportByDay;
use App\Sale\Domain\ReadModel\SalesReportByProduct;
use App\Sale\Domain\ReadModel\SalesReportByUser;
use App\Sale\Domain\ReadModel\SalesReportByZone;
use App\Sale\Domain\ReadModel\SaleSummary;
use Illuminate\Support\Facades\DB;

class EloquentSaleReportRepository implements SaleReportRepositoryInterface
{
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
                's.subtotal',
                's.tax_amount',
                's.line_discount_total',
                's.order_discount_total',
                's.refunded_total',
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

        return $query->get()->map(fn($row) => new SaleSummary(
            uuid: $row->uuid,
            ticketNumber: (int) $row->ticket_number,
            valueDate: $row->value_date,
            subtotal: (int) $row->subtotal,
            taxAmount: (int) $row->tax_amount,
            lineDiscountTotal: (int) $row->line_discount_total,
            orderDiscountTotal: (int) $row->order_discount_total,
            total: (int) $row->total,
            refundedTotal: (int) $row->refunded_total,
            netTotal: (int) $row->total - (int) $row->refunded_total,
            tableName: $row->table_name,
            openUserName: $row->open_user_name,
            closeUserName: $row->close_user_name ?? '—',
            openedAt: $row->opened_at,
            closedAt: $row->closed_at,
        ))->all();
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
                'sl.line_subtotal',
                'sl.tax_amount',
                'sl.discount_type',
                'sl.discount_value',
                'sl.discount_amount',
                'sl.line_total',
                'sl.refunded_quantity',
            )
            ->get()
            ->map(fn($row) => new SaleLineDetail(
                uuid: $row->uuid,
                productName: $row->product_name,
                quantity: (int) $row->quantity,
                price: (int) $row->price,
                taxPercentage: (int) $row->tax_percentage,
                lineSubtotal: (int) $row->line_subtotal,
                taxAmount: (int) $row->tax_amount,
                discountType: $row->discount_type,
                discountValue: (int) $row->discount_value,
                discountAmount: (int) $row->discount_amount,
                lineTotal: (int) $row->line_total,
                refundedQuantity: (int) $row->refunded_quantity,
            ))
            ->all();
    }

    public function getGroupedReport(int $restaurantId, ?string $from, ?string $to): SalesGroupedReport
    {
        $applyFilters = function ($q) use ($restaurantId, $from, $to) {
            $q->where('s.restaurant_id', $restaurantId)->whereNull('s.deleted_at');
            if ($from !== null) $q->where('s.value_date', '>=', $from . ' 00:00:00');
            if ($to !== null) $q->where('s.value_date', '<=', $to . ' 23:59:59');
            return $q;
        };

        $byDay = $applyFilters(DB::table('sales as s'))
            ->select(DB::raw('DATE(s.value_date) as day'), DB::raw('COUNT(*) as count'), DB::raw('SUM(s.total - s.refunded_total) as total'))
            ->groupBy('day')->orderBy('day')
            ->get()->map(fn($r) => new SalesReportByDay(day: $r->day, count: (int) $r->count, total: (int) $r->total))->all();

        $byZone = $applyFilters(DB::table('sales as s'))
            ->join('orders as o', 's.order_id', '=', 'o.id')
            ->join('tables as t', 'o.table_id', '=', 't.id')
            ->join('zones as z', 't.zone_id', '=', 'z.id')
            ->select('z.name as zone_name', DB::raw('COUNT(*) as count'), DB::raw('SUM(s.total - s.refunded_total) as total'))
            ->groupBy('z.id', 'z.name')->orderByDesc('total')
            ->get()->map(fn($r) => new SalesReportByZone(zoneName: $r->zone_name, count: (int) $r->count, total: (int) $r->total))->all();

        $byProduct = $applyFilters(DB::table('sales as s'))
            ->join('sales_lines as sl', 'sl.sale_id', '=', 's.id')
            ->join('order_lines as ol', 'sl.order_line_id', '=', 'ol.id')
            ->join('products as p', 'ol.product_id', '=', 'p.id')
            ->whereNull('sl.deleted_at')
            ->select('p.name as product_name', DB::raw('SUM(sl.quantity - sl.refunded_quantity) as total_quantity'), DB::raw('SUM(sl.line_total - ((sl.refunded_quantity / sl.quantity) * sl.line_total)) as total'))
            ->groupBy('p.id', 'p.name')->orderByDesc('total_quantity')
            ->get()->map(fn($r) => new SalesReportByProduct(productName: $r->product_name, totalQuantity: (int) $r->total_quantity, total: (int) $r->total))->all();

        $byUser = $applyFilters(DB::table('sales as s'))
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select('u.name as user_name', DB::raw('COUNT(*) as count'), DB::raw('SUM(s.total - s.refunded_total) as total'))
            ->groupBy('u.id', 'u.name')->orderByDesc('total')
            ->get()->map(fn($r) => new SalesReportByUser(userName: $r->user_name, count: (int) $r->count, total: (int) $r->total))->all();

        return new SalesGroupedReport(
            byDay: $byDay,
            byZone: $byZone,
            byProduct: $byProduct,
            byUser: $byUser,
        );
    }
}