<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetSaleReceipt\GetSaleReceipt;
use App\Sale\Domain\ReadModel\SaleLineDetail;
use App\Sale\Domain\ReadModel\SaleReceipt;
use App\Sale\Domain\ReadModel\SaleServiceWindow;
use App\Sale\Domain\ReadModel\SaleServiceWindowLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSaleReceiptController
{
    public function __construct(
        private GetSaleReceipt $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $receipt = ($this->useCase)($request->user()->restaurant_id, $uuid);

        if (!$receipt instanceof SaleReceipt) {
            return new JsonResponse(['message' => 'Venta no encontrada'], 404);
        }

        return new JsonResponse($this->toArray($receipt));
    }

    private function toArray(SaleReceipt $receipt): array
    {
        return [
            'restaurant_name' => $receipt->restaurantName,
            'restaurant_legal_name' => $receipt->restaurantLegalName,
            'restaurant_tax_id' => $receipt->restaurantTaxId,
            'ticket_number' => $receipt->ticketNumber,
            'value_date' => $receipt->valueDate,
            'table_name' => $receipt->tableName,
            'opened_at' => $receipt->openedAt,
            'closed_at' => $receipt->closedAt,
            'open_user_name' => $receipt->openUserName,
            'close_user_name' => $receipt->closeUserName,
            'subtotal' => $receipt->subtotal,
            'tax_amount' => $receipt->taxAmount,
            'line_discount_total' => $receipt->lineDiscountTotal,
            'order_discount_total' => $receipt->orderDiscountTotal,
            'total' => $receipt->total,
            'refunded_total' => $receipt->refundedTotal,
            'net_total' => $receipt->netTotal,
            'lines' => array_map(fn (SaleLineDetail $line) => [
                'uuid' => $line->uuid,
                'product_name' => $line->productName,
                'quantity' => $line->quantity,
                'price' => $line->price,
                'tax_percentage' => $line->taxPercentage,
                'line_subtotal' => $line->lineSubtotal,
                'tax_amount' => $line->taxAmount,
                'discount_type' => $line->discountType,
                'discount_value' => $line->discountValue,
                'discount_amount' => $line->discountAmount,
                'line_total' => $line->lineTotal,
                'refunded_quantity' => $line->refundedQuantity,
            ], $receipt->lines),
            'service_windows' => array_map(fn (SaleServiceWindow $window) => [
                'uuid' => $window->uuid,
                'window_number' => $window->windowNumber,
                'sent_at' => $window->sentAt,
                'sent_by_user_name' => $window->sentByUserName,
                'lines' => array_map(fn (SaleServiceWindowLine $line) => [
                    'uuid' => $line->uuid,
                    'product_name' => $line->productName,
                    'quantity' => $line->quantity,
                    'price' => $line->price,
                    'tax_percentage' => $line->taxPercentage,
                    'discount_type' => $line->discountType,
                    'discount_value' => $line->discountValue,
                    'discount_amount' => $line->discountAmount,
                    'line_subtotal' => $line->lineSubtotal,
                    'tax_amount' => $line->taxAmount,
                    'line_total' => $line->lineTotal,
                ], $window->lines),
            ], $receipt->serviceWindows),
        ];
    }
}
