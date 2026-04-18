<?php

declare(strict_types=1);

namespace App\Product\Application\CreateProduct;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;

class CreateProduct
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {}

    public function __invoke(
        string $name,
        int $price,
        int $stock,
        bool $active,
        string $familyId,
        string $taxId,
        int $restaurantId,
        ?string $imageSrc = null,
    ): CreateProductResponse {
        $product = Product::dddCreate(
            Uuid::generate(),
            ProductName::create($name),
            ProductPrice::create($price),
            ProductStock::create($stock),
            $active,
            Uuid::create($familyId),
            Uuid::create($taxId),
            $restaurantId,
            $imageSrc,
        );

        $this->repository->save($product);

        return CreateProductResponse::create($product);
    }
}
