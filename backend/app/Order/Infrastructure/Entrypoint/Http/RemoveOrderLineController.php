<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\RemoveOrderLine\RemoveOrderLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemoveOrderLineController
{
    public function __construct(private RemoveOrderLine $useCase) {}

    public function __invoke(Request $request, string $orderUuid, string $lineUuid): JsonResponse
    {
        ($this->useCase)($orderUuid, $lineUuid, $request->user()->restaurant_id);

        return new JsonResponse(null, 204);
    }
}
