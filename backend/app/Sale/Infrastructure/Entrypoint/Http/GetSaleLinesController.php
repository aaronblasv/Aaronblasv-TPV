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

        return new JsonResponse($lines);
    }
}
