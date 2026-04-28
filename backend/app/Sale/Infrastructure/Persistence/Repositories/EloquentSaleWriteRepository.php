<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Domain\Interfaces\SaleWriteRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class EloquentSaleWriteRepository implements SaleWriteRepositoryInterface
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
        $orderId = $this->resolveOrderId($sale->orderId()->getValue());
        $userId = $this->resolveUserId($sale->userId()->getValue());

        $this->model->newQuery()->create([
            'uuid' => $sale->uuid()->getValue(),
            'restaurant_id' => $sale->restaurantId(),
            'order_id' => $orderId,
            'user_id' => $userId,
            'ticket_number' => $sale->ticketNumber(),
            'value_date' => $sale->valueDate()->format('Y-m-d H:i:s'),
            'subtotal' => $sale->subtotal(),
            'tax_amount' => $sale->taxAmount(),
            'line_discount_total' => $sale->lineDiscountTotal(),
            'order_discount_total' => $sale->orderDiscountTotal(),
            'total' => $sale->total(),
            'refunded_total' => $sale->refundedTotal(),
        ]);
    }

    public function saveLine(SaleLine $line): void
    {
        $saleId = $this->resolveSaleId($line->saleId()->getValue());
        $orderLineId = $this->resolveOrderLineId($line->orderLineId()->getValue());
        $userId = $this->resolveUserId($line->userId()->getValue());

        $this->saleLineModel->newQuery()->create([
            'uuid' => $line->uuid()->getValue(),
            'restaurant_id' => $line->restaurantId(),
            'sale_id' => $saleId,
            'order_line_id' => $orderLineId,
            'product_name' => $line->productName(),
            'user_id' => $userId,
            'quantity' => $line->quantity(),
            'price' => $line->price(),
            'tax_percentage' => $line->taxPercentage(),
            'line_subtotal' => $line->lineSubtotal(),
            'tax_amount' => $line->taxAmount(),
            'discount_type' => $line->discountType(),
            'discount_value' => $line->discountValue(),
            'discount_amount' => $line->discountAmount(),
            'line_total' => $line->lineTotal(),
            'refunded_quantity' => $line->refundedQuantity(),
        ]);
    }

    public function saveLinesBatch(array $lines): void
    {
        if ($lines === []) {
            return;
        }

        /** @var SaleLine $firstLine */
        $firstLine = $lines[0];

        $saleId = $this->model->newQuery()
            ->where('uuid', $firstLine->saleId()->getValue())
            ->value('id');

        if ($saleId === null) {
            throw new SaleNotFoundException($firstLine->saleId()->getValue());
        }

        $orderLineIds = $this->orderLineModel->newQuery()
            ->whereIn('uuid', array_map(fn (SaleLine $line): string => $line->orderLineId()->getValue(), $lines))
            ->pluck('id', 'uuid')
            ->all();

        $userIds = $this->userModel->newQuery()
            ->whereIn('uuid', array_map(fn (SaleLine $line): string => $line->userId()->getValue(), $lines))
            ->pluck('id', 'uuid')
            ->all();

        $timestamp = now();
        $rows = [];

        foreach ($lines as $line) {
            $rows[] = [
                'uuid' => $line->uuid()->getValue(),
                'restaurant_id' => $line->restaurantId(),
                'sale_id' => $saleId,
                'order_line_id' => $orderLineIds[$line->orderLineId()->getValue()],
                'product_name' => $line->productName(),
                'user_id' => $userIds[$line->userId()->getValue()],
                'quantity' => $line->quantity(),
                'price' => $line->price(),
                'tax_percentage' => $line->taxPercentage(),
                'line_subtotal' => $line->lineSubtotal(),
                'tax_amount' => $line->taxAmount(),
                'discount_type' => $line->discountType(),
                'discount_value' => $line->discountValue(),
                'discount_amount' => $line->discountAmount(),
                'line_total' => $line->lineTotal(),
                'refunded_quantity' => $line->refundedQuantity(),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $this->saleLineModel->newQuery()->insert($rows);
    }

    public function update(Sale $sale): void
    {
        try {
            $this->model->newQuery()
                ->where('uuid', $sale->uuid()->getValue())
                ->firstOrFail()
                ->update([
                    'subtotal' => $sale->subtotal(),
                    'tax_amount' => $sale->taxAmount(),
                    'line_discount_total' => $sale->lineDiscountTotal(),
                    'order_discount_total' => $sale->orderDiscountTotal(),
                    'total' => $sale->total(),
                    'refunded_total' => $sale->refundedTotal(),
                ]);
        } catch (ModelNotFoundException) {
            throw new SaleNotFoundException($sale->uuid()->getValue());
        }
    }

    public function updateLine(SaleLine $line): void
    {
        try {
            $this->saleLineModel->newQuery()
                ->where('uuid', $line->uuid()->getValue())
                ->firstOrFail()
                ->update([
                    'refunded_quantity' => $line->refundedQuantity(),
                ]);
        } catch (ModelNotFoundException) {
            throw new SaleNotFoundException($line->uuid()->getValue());
        }
    }

    public function getNextTicketNumber(int $restaurantId): int
    {
        if (DB::getDriverName() !== 'mysql') {
            return DB::transaction(function () use ($restaurantId): int {
                DB::table('restaurant_ticket_counters')->insertOrIgnore([
                    'restaurant_id' => $restaurantId,
                    'last_ticket_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $current = (int) DB::table('restaurant_ticket_counters')
                    ->where('restaurant_id', $restaurantId)
                    ->value('last_ticket_number');

                $next = $current + 1;

                DB::table('restaurant_ticket_counters')
                    ->where('restaurant_id', $restaurantId)
                    ->update([
                        'last_ticket_number' => $next,
                        'updated_at' => now(),
                    ]);

                return $next;
            });
        }

        DB::statement(
            'INSERT INTO restaurant_ticket_counters (restaurant_id, last_ticket_number, created_at, updated_at)
            VALUES (?, 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)',
            [$restaurantId],
        );

        DB::statement(
            'UPDATE restaurant_ticket_counters
             SET last_ticket_number = LAST_INSERT_ID(last_ticket_number + 1), updated_at = NOW()
             WHERE restaurant_id = ?',
            [$restaurantId],
        );

        return (int) DB::selectOne('SELECT LAST_INSERT_ID() AS n')->n;
    }

    private function resolveOrderId(string $orderUuid): int
    {
        try {
            return $this->orderModel->newQuery()->where('uuid', $orderUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new OrderNotFoundException($orderUuid);
        }
    }

    private function resolveOrderLineId(string $orderLineUuid): int
    {
        try {
            return $this->orderLineModel->newQuery()->where('uuid', $orderLineUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new OrderLineNotFoundException($orderLineUuid);
        }
    }

    private function resolveSaleId(string $saleUuid): int
    {
        try {
            return $this->model->newQuery()->where('uuid', $saleUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new SaleNotFoundException($saleUuid);
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
}
