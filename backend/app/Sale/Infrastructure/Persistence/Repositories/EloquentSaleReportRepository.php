<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Interfaces\SaleReportRepositoryInterface;
use App\Sale\Domain\ReadModel\SaleLineDetail;
use App\Sale\Domain\ReadModel\SaleReceipt;
use App\Sale\Domain\ReadModel\SaleServiceWindow;
use App\Sale\Domain\ReadModel\SaleServiceWindowLine;
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
            ->leftJoin('products as p', 'ol.product_id', '=', 'p.id')
            ->where('sl.sale_id', $saleId)
            ->whereNull('sl.deleted_at')
            ->select(
                'sl.uuid',
                DB::raw("COALESCE(sl.product_name, p.name, 'Producto eliminado') as product_name"),
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

    public function findReceiptBySaleUuid(int $restaurantId, string $saleUuid): ?SaleReceipt
    {
        $sale = DB::table('sales as s')
            ->join('orders as o', 's.order_id', '=', 'o.id')
            ->join('tables as t', 'o.table_id', '=', 't.id')
            ->join('users as open_user', 'o.opened_by_user_id', '=', 'open_user.id')
            ->leftJoin('users as close_user', 'o.closed_by_user_id', '=', 'close_user.id')
            ->join('restaurants as r', 's.restaurant_id', '=', 'r.id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.uuid', $saleUuid)
            ->whereNull('s.deleted_at')
            ->select(
                's.uuid',
                's.ticket_number',
                's.value_date',
                's.subtotal',
                's.tax_amount',
                's.line_discount_total',
                's.order_discount_total',
                's.total',
                's.refunded_total',
                't.name as table_name',
                'open_user.name as open_user_name',
                'close_user.name as close_user_name',
                'o.opened_at',
                'o.closed_at',
                'o.uuid as order_uuid',
                'r.name as restaurant_name',
                'r.legal_name as restaurant_legal_name',
                'r.tax_id as restaurant_tax_id',
            )
            ->first();

        if ($sale === null) {
            return null;
        }

        $lines = $this->findLinesBySaleUuid($restaurantId, $saleUuid);

        $windows = DB::table('order_service_windows as sw')
            ->join('orders as o', 'sw.order_id', '=', 'o.id')
            ->where('sw.restaurant_id', $restaurantId)
            ->where('o.uuid', $sale->order_uuid)
            ->whereNull('sw.deleted_at')
            ->orderBy('sw.window_number')
            ->select('sw.id', 'sw.uuid', 'sw.window_number', 'sw.sent_at', 'sw.sent_by_user_name')
            ->get();

        $windowIds = $windows->pluck('id')->all();
        $windowLinesByWindowId = [];

        if ($windowIds !== []) {
            $windowLinesByWindowId = DB::table('order_service_window_lines as swl')
                ->where('swl.restaurant_id', $restaurantId)
                ->whereIn('swl.order_service_window_id', $windowIds)
                ->whereNull('swl.deleted_at')
                ->orderBy('swl.id')
                ->select(
                    'swl.order_service_window_id',
                    'swl.uuid',
                    'swl.product_name',
                    'swl.quantity',
                    'swl.price',
                    'swl.tax_percentage',
                    'swl.discount_type',
                    'swl.discount_value',
                    'swl.discount_amount',
                    'swl.line_subtotal',
                    'swl.tax_amount',
                    'swl.line_total',
                )
                ->get()
                ->groupBy('order_service_window_id')
                ->map(fn ($rows) => $rows->map(fn ($row) => new SaleServiceWindowLine(
                    uuid: $row->uuid,
                    productName: $row->product_name,
                    quantity: (int) $row->quantity,
                    price: (int) $row->price,
                    taxPercentage: (int) $row->tax_percentage,
                    discountType: $row->discount_type,
                    discountValue: (int) $row->discount_value,
                    discountAmount: (int) $row->discount_amount,
                    lineSubtotal: (int) $row->line_subtotal,
                    taxAmount: (int) $row->tax_amount,
                    lineTotal: (int) $row->line_total,
                ))->all())
                ->all();
        }

        $serviceWindows = $windows->map(fn ($window) => new SaleServiceWindow(
            uuid: $window->uuid,
            windowNumber: (int) $window->window_number,
            sentAt: $window->sent_at,
            sentByUserName: $window->sent_by_user_name,
            lines: $windowLinesByWindowId[$window->id] ?? [],
        ))->all();

        return new SaleReceipt(
            restaurantName: $sale->restaurant_name,
            restaurantLegalName: $sale->restaurant_legal_name,
            restaurantTaxId: $sale->restaurant_tax_id,
            ticketNumber: (int) $sale->ticket_number,
            valueDate: $sale->value_date,
            tableName: $sale->table_name,
            openedAt: $sale->opened_at,
            closedAt: $sale->closed_at,
            openUserName: $sale->open_user_name,
            closeUserName: $sale->close_user_name ?? '—',
            subtotal: (int) $sale->subtotal,
            taxAmount: (int) $sale->tax_amount,
            lineDiscountTotal: (int) $sale->line_discount_total,
            orderDiscountTotal: (int) $sale->order_discount_total,
            total: (int) $sale->total,
            refundedTotal: (int) $sale->refunded_total,
            netTotal: (int) $sale->total - (int) $sale->refunded_total,
            lines: $lines,
            serviceWindows: $serviceWindows,
        );
    }

    public function getGroupedReport(int $restaurantId, ?string $from, ?string $to): SalesGroupedReport
    {
        $applyFilters = function ($q) use ($restaurantId, $from, $to) {
            $q->where('s.restaurant_id', $restaurantId)->whereNull('s.deleted_at');
            if ($from !== null) $q->where('s.value_date', '>=', $from . ' 00:00:00');
            if ($to !== null) $q->where('s.value_date', '<=', $to . ' 23:59:59');
            return $q;
        };

        $refundTotalsBySaleLine = DB::table('refund_lines')
            ->select(
                'sale_line_id',
                DB::raw('SUM(quantity) as refunded_quantity'),
                DB::raw('SUM(total) as refunded_total'),
            )
            ->groupBy('sale_line_id');

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
            ->leftJoinSub($refundTotalsBySaleLine, 'refund_totals', function ($join) {
                $join->on('refund_totals.sale_line_id', '=', 'sl.id');
            })
            ->join('order_lines as ol', 'sl.order_line_id', '=', 'ol.id')
            ->leftJoin('products as p', 'ol.product_id', '=', 'p.id')
            ->whereNull('sl.deleted_at')
            ->select(
                DB::raw("COALESCE(sl.product_name, p.name, 'Producto eliminado') as product_name"),
                DB::raw('SUM(sl.quantity - COALESCE(refund_totals.refunded_quantity, 0)) as total_quantity'),
                DB::raw('SUM(sl.line_total - COALESCE(refund_totals.refunded_total, 0)) as total'),
            )
            ->groupBy(DB::raw("COALESCE(sl.product_name, p.name, 'Producto eliminado')"))->orderByDesc('total_quantity')
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