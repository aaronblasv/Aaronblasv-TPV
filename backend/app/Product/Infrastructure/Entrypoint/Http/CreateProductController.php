<?php
namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\CreateProduct\CreateProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateProductController
{
    public function __construct(private CreateProduct $useCase) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'family_id' => 'required|string',
            'tax_id' => 'required|string',
        ]);
        $product = ($this->useCase)(
            $validated['name'],
            $validated['price'],
            $validated['stock'],
            $request->input('active', true),
            $validated['family_id'],
            $validated['tax_id'],
        );
        return new JsonResponse($product, 201);
    }
}