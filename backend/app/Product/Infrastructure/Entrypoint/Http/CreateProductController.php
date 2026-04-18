<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\CreateProduct\CreateProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateProductController
{
    public function __construct(
        private CreateProduct $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'family_id' => 'required|uuid',
            'tax_id' => 'required|uuid',
            'image_src' => 'nullable|string',
        ]);

        $product = ($this->useCase)(
            $validated['name'],
            $validated['price'],
            $validated['stock'],
            $request->boolean('active', true),
            $validated['family_id'],
            $validated['tax_id'],
            $request->user()->restaurant_id,
            $validated['image_src'] ?? null,
        );

        return new JsonResponse($product->toArray(), 201);
    }
}
