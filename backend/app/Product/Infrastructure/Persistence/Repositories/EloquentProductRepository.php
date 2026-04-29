<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence\Repositories;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private EloquentProduct $model,
        private EloquentFamily $familyModel,
        private EloquentTax $taxModel,
    ) {}

    public function findAll(int $restaurantId): array
    {
        return $this->model->newQuery()
            ->with(['family', 'tax'])
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(fn(EloquentProduct $product) => $this->toDomain($product))
            ->toArray();
    }

    public function save(Product $product): void
    {
        $family = $this->familyModel->newQuery()
            ->withTrashed()
            ->where('uuid', $product->familyId()->getValue())
            ->firstOrFail();

        $tax = $this->taxModel->newQuery()
            ->withTrashed()
            ->where('uuid', $product->taxId()->getValue())
            ->firstOrFail();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $product->uuid()->getValue()],
            [
                'name' => $product->name()->getValue(),
                'price' => $product->price()->getValue(),
                'stock' => $product->stock()->getValue(),
                'active' => $product->active(),
                'family_id' => $family->id,
                'tax_id' => $tax->id,
                'restaurant_id' => $product->restaurantId(),
                'image_src' => $product->imageSrc(),
            ]
        );
    }

    public function findById(string $productUuid, int $restaurantId): ?Product
    {
        $product = $this->model->newQuery()
            ->with(['family', 'tax'])
            ->where('uuid', $productUuid)
            ->where('restaurant_id', $restaurantId)
            ->first();

        return $product ? $this->toDomain($product) : null;
    }

    public function findByIds(array $productUuids, int $restaurantId): array
    {
        if ($productUuids === []) {
            return [];
        }

        return $this->model->newQuery()
            ->with(['family', 'tax'])
            ->where('restaurant_id', $restaurantId)
            ->whereIn('uuid', array_values(array_unique($productUuids)))
            ->get()
            ->mapWithKeys(fn (EloquentProduct $product) => [$product->uuid => $this->toDomain($product)])
            ->all();
    }

    public function delete(string $productUuid, int $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $productUuid)
            ->where('restaurant_id', $restaurantId)
            ->delete();
    }

    private function toDomain(EloquentProduct $product): Product
    {
        $family = $product->relationLoaded('family')
            ? $product->family
            : $this->familyModel->newQuery()->withTrashed()->find($product->family_id);
        $tax = $product->relationLoaded('tax')
            ? $product->tax
            : $this->taxModel->newQuery()->withTrashed()->find($product->tax_id);

        return Product::fromPersistence(
            $product->uuid,
            $product->name,
            $product->price,
            $product->stock,
            (bool) $product->active,
            $family->uuid,
            $tax->uuid,
            $product->restaurant_id,
            $product->image_src,
        );
    }
}
