<?php

declare(strict_types=1);

namespace App\Product\Application\UpdateProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateProduct
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {}

    public function __invoke(
        string $uuid,
        string $name,
        int $price,
        int $stock,
        bool $active,
        string $familyId,
        string $taxId,
        int $restaurantId,
        ?string $imageSrc = null,
    ): UpdateProductResponse {
        $product = $this->repository->findById($uuid, $restaurantId);

        if ($product === null) {
            throw new ProductNotFoundException($uuid);
        }

        $product->dddUpdate(
            ProductName::create($name),
            ProductPrice::create($price),
            ProductStock::create($stock),
            $active,
            Uuid::create($familyId),
            Uuid::create($taxId),
            $imageSrc,
        );

        $this->repository->save($product);

        return UpdateProductResponse::create($product);
    }
}
