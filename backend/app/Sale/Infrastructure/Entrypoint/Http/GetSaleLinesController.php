<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetSaleLines\GetSaleLines;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSaleLinesController
{
    public function __construct(
        private GetSaleLines $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $lines = ($this->useCase)($request->user()->restaurant_id, $uuid);

        return new JsonResponse(
            array_map(fn($l) => [
                'uuid' => $l->uuid,
                'product_name' => $l->productName,
                'quantity' => $l->quantity,
                'price' => $l->price,
                'tax_percentage' => $l->taxPercentageAsPercentage(),
                'line_subtotal' => $l->lineSubtotal,
                'tax_amount' => $l->taxAmount,
                'discount_type' => $l->discountType,
                'discount_value' => $l->discountValue,
                'discount_amount' => $l->discountAmount,
                'line_total' => $l->lineTotal,
                'refunded_quantity' => $l->refundedQuantity,
            ], $lines)
        );
    }
}
