<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\UpdateTax\UpdateTax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateTaxController
{
    public function __construct(
        private UpdateTax $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        $tax = ($this->useCase)($uuid, $validated['name'], $validated['percentage']);

        return new JsonResponse($tax);
    }
}