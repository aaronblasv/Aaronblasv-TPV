<?php

declare(strict_types=1);

namespace App\Product\Application\ActivateProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class ActivateProduct
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

        $product->activate();

        $this->repository->save($product);
    }
}
