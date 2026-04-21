<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleWriteRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

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
        $orderId = $this->orderModel->newQuery()->where('uuid', $sale->orderId()->getValue())->firstOrFail()->id;
        $userId = $this->userModel->newQuery()->where('uuid', $sale->userId()->getValue())->firstOrFail()->id;

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
        $saleId = $this->model->newQuery()->where('uuid', $line->saleId()->getValue())->firstOrFail()->id;
        $orderLineId = $this->orderLineModel->newQuery()->where('uuid', $line->orderLineId()->getValue())->firstOrFail()->id;
        $userId = $this->userModel->newQuery()->where('uuid', $line->userId()->getValue())->firstOrFail()->id;

        $this->saleLineModel->newQuery()->create([
            'uuid' => $line->uuid()->getValue(),
            'restaurant_id' => $line->restaurantId(),
            'sale_id' => $saleId,
            'order_line_id' => $orderLineId,
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

    public function update(Sale $sale): void
    {
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
    }

    public function updateLine(SaleLine $line): void
    {
        $this->saleLineModel->newQuery()
            ->where('uuid', $line->uuid()->getValue())
            ->firstOrFail()
            ->update([
                'refunded_quantity' => $line->refundedQuantity(),
            ]);
    }

    public function getNextTicketNumber(int $restaurantId): int
    {
        $last = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->max('ticket_number');

        return ($last ?? 0) + 1;
    }
}