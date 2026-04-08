<?php
namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\UpdateProduct\UpdateProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateProductController
{
    public function __construct(private UpdateProduct $useCase) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'family_id' => 'required|string',
            'tax_id' => 'required|string',
        ]);
        $product = ($this->useCase)(
            $uuid,
            $validated['name'],
            $validated['price'],
            $validated['stock'],
            $request->input('active'),
            $validated['family_id'],
            $validated['tax_id'],
        );
        return new JsonResponse($product);
    }
}