<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\GetAllProducts\GetAllProducts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllProductsController
{
    public function __construct(
        private GetAllProducts $useCase,
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
