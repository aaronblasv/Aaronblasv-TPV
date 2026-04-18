<?php

declare(strict_types=1);

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\CreateTax\CreateTax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateTaxController
{
    public function __construct(
        private CreateTax $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        $tax = ($this->useCase)(
            $validated['name'],
            $validated['percentage'],
            $request->user()->restaurant_id,
        );

        return new JsonResponse($tax->toArray(), 201);
    }
}
