<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\DeleteProduct\DeleteProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteProductController
{
    public function __construct(
        private DeleteProduct $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        ($this->useCase)($uuid, $request->user()->restaurant_id);

        return new JsonResponse(null, 204);
    }
}
