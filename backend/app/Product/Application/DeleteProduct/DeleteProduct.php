<?php

namespace App\Product\Application\DeleteProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class DeleteProduct
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {}

    public function __invoke(string $uuid, int $restaurantId): void
    {
        $product = $this->repository->findById($uuid, $restaurantId);

        if ($product === null) {
            throw new ProductNotFoundException($uuid);
        }

        $this->repository->delete($uuid, $restaurantId);
    }
}
