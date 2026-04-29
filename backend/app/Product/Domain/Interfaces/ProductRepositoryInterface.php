<?php

declare(strict_types=1);

namespace App\Product\Domain\Interfaces;

use App\Product\Domain\Entity\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;

    public function findById(string $productUuid, int $restaurantId): ?Product;

    /**
     * @return array<string, Product>
     */
    public function findByIds(array $productUuids, int $restaurantId): array;

    public function findAll(int $restaurantId): array;

    public function delete(string $productUuid, int $restaurantId): void;
}
