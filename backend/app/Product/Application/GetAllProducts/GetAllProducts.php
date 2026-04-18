<?php

declare(strict_types=1);

namespace App\Product\Application\GetAllProducts;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class GetAllProducts
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {}

    public function __invoke(int $restaurantId): array
    {
        $products = $this->repository->findAll($restaurantId);

        return array_map(
            fn(Product $product) => GetAllProductsResponse::create($product),
            $products
        );
    }
}
