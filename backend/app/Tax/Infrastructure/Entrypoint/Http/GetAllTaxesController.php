<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\GetAllTaxes\GetAllTaxes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllTaxesController
{
    public function __construct(
        private GetAllTaxes $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $taxes = ($this->useCase)($request->user()->restaurant_id);

        return new JsonResponse(array_map(
            static fn($item) => $item->toArray(),
            $taxes,
        ));
    }
}
