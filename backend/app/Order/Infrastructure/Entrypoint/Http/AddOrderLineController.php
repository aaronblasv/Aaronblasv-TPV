<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\AddOrderLine\AddOrderLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddOrderLineController
{
    public function __construct(private AddOrderLine $useCase) {}

    public function __invoke(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'product_id'     => 'required|string',
            'user_id'        => 'required|string',
            'quantity'       => 'required|integer|min:1',
            'price'          => 'required|integer|min:0',
            'tax_percentage' => 'required|integer|min:0|max:100',
        ]);

        $response = ($this->useCase)(
            $request->user()->restaurant_id,
            $orderUuid,
            $validated['product_id'],
            $validated['user_id'],
            $validated['quantity'],
            $validated['price'],
            $validated['tax_percentage'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
