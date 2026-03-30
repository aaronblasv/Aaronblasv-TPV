<?php

namespace App\Product\Infrastructure\Persistence\Repositories;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductName;
use App\Shared\Domain\ValueObject\Uuid;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;


class EloquentProductRepository implements ProductRepositoryInterface
{

    public function findAll(): array
    {
        return EloquentProduct::all()->map(
            fn(EloquentProduct $product) => $this->toDomain($product)
        )->toArray();
    }

    private function toDomain(EloquentProduct $product): Product
    {
        $family = EloquentFamily::withTrashed()->find($product->family_id);
        $tax = EloquentTax::withTrashed()->find($product->tax_id);

        return Product::dddCreate(
            Uuid::create($product->uuid),
            ProductName::create($product->name),
            ProductPrice::create($product->price),
            ProductStock::create($product->stock),
            (bool) $product->active,
            $family->uuid,
            $tax->uuid,
        );
    }

    public function save(Product $product): void
    {
        $family = EloquentFamily::withTrashed()->where('uuid', $product->getFamilyId())->first();
        $tax = EloquentTax::withTrashed()->where('uuid', $product->getTaxId())->first();

        EloquentProduct::updateOrCreate(
            ['uuid' => $product->getUuid()->getValue()],
            [
                'name' => $product->getName()->getValue(),
                'price' => $product->getPrice()->getValue(),
                'stock' => $product->getStock()->getValue(),
                'active' => $product->isActive(),
                'family_id' => $family->id,
                'tax_id' => $tax->id,
                'restaurant_id' => auth()->user()->restaurant_id,
            ]
        );
    }

    public function findById(string $id): ?Product
    {
        $product = EloquentProduct::where('uuid', $id)->first();

        return $product ? $this->toDomain($product) : null;
    }

    public function delete(string $id): void
    {
        EloquentProduct::where('uuid', $id)->delete();
    }
}