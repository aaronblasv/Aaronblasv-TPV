<?php

declare(strict_types=1);

namespace App\Product\Application\DeactivateProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class DeactivateProduct
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

        $product->deactivate();

        $this->repository->save($product);
    }
}
