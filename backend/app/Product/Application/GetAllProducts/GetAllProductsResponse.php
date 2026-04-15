<?php

namespace App\Product\Application\GetAllProducts;

use App\Product\Domain\Entity\Product;

final readonly class GetAllProductsResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
        public string $familyId,
        public string $taxId,
        public ?string $imageSrc = null,
    ) {}

    public static function create(Product $product): self
    {
        return new self(
            $product->uuid()->getValue(),
            $product->name()->getValue(),
            $product->price()->getValue(),
            $product->stock()->getValue(),
            $product->active(),
            $product->familyId()->getValue(),
            $product->taxId()->getValue(),
            $product->imageSrc(),
        );
    }

    /**
     * @return array<string, string|int|bool|null>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'price' => $this->price,
            'stock' => $this->stock,
            'active' => $this->active,
            'family_id' => $this->familyId,
            'tax_id' => $this->taxId,
            'image_src' => $this->imageSrc,
        ];
    }
}
