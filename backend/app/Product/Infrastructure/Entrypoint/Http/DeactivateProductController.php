<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\DeactivateProduct\DeactivateProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeactivateProductController
{
    public function __construct(
        private DeactivateProduct $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        ($this->useCase)($uuid, $request->user()->restaurant_id);

        return new JsonResponse(null, 204);
    }
}
