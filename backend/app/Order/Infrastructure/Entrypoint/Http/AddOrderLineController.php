<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\AddOrderLine\AddOrderLine;
use App\Shared\Application\Context\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddOrderLineController
{
    public function __construct(private AddOrderLine $useCase) {}

    public function __invoke(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'product_id'     => 'required|uuid',
            'user_id'        => 'required|uuid',
            'quantity'       => 'required|integer|min:1',
        ]);

        $response = ($this->useCase)(
            new AuditContext(
                $request->user()->restaurant_id,
                $request->user()->uuid,
                $request->ip(),
            ),
            $orderUuid,
            $validated['product_id'],
            $validated['user_id'],
            $validated['quantity'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
