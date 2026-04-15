<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetAllSales\GetAllSales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllSalesController
{
    public function __construct(
        private GetAllSales $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $response = ($this->useCase)($request->user()->restaurant_id);

        return new JsonResponse(array_map(
            static fn($item) => $item->toArray(),
            $response,
        ));
    }
}
