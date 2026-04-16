<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetAllOpenOrders\GetAllOpenOrders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllOpenOrdersController
{
    public function __construct(private GetAllOpenOrders $useCase) {}

    public function __invoke(Request $request): JsonResponse
    {
        $orders = ($this->useCase)($request->user()->restaurant_id);

        return new JsonResponse(array_map(fn($r) => $r->toArray(), $orders));
    }
}
