<?php

declare(strict_types=1);

namespace App\Product\Domain\Entity;

use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;

class Product
{
    private function __construct(
        private Uuid $uuid,
        private ProductName $name,
        private ProductPrice $price,
        private ProductStock $stock,
        private bool $active,
        private Uuid $familyId,
        private Uuid $taxId,
        private RestaurantId $restaurantId,
        private ?string $imageSrc = null,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        ProductName $name,
        ProductPrice $price,
        ProductStock $stock,
        bool $active,
        Uuid $familyId,
        Uuid $taxId,
        int $restaurantId,
        ?string $imageSrc = null,
    ): self {
        return new self($uuid, $name, $price, $stock, $active, $familyId, $taxId, RestaurantId::create($restaurantId), $imageSrc);
    }

    public static function fromPersistence(
        string $uuid,
        string $name,
        int $price,
        int $stock,
        bool $active,
        string $familyId,
        string $taxId,
        int $restaurantId,
        ?string $imageSrc = null,
    ): self {
        return new self(
            Uuid::create($uuid),
            ProductName::create($name),
            ProductPrice::create($price),
            ProductStock::create($stock),
            $active,
            Uuid::create($familyId),
            Uuid::create($taxId),
            RestaurantId::create($restaurantId),
            $imageSrc,
        );
    }

    public function dddUpdate(
        ProductName $name,
        ProductPrice $price,
        ProductStock $stock,
        bool $active,
        Uuid $familyId,
        Uuid $taxId,
        ?string $imageSrc = null,
    ): void {
        $this->name = $name;
        $this->price = $price;
        $this->stock = $stock;
        $this->active = $active;
        $this->familyId = $familyId;
        $this->taxId = $taxId;
        $this->imageSrc = $imageSrc;
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function name(): ProductName { return $this->name; }
    public function price(): ProductPrice { return $this->price; }
    public function stock(): ProductStock { return $this->stock; }
    public function active(): bool { return $this->active; }
    public function familyId(): Uuid { return $this->familyId; }
    public function taxId(): Uuid { return $this->taxId; }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function imageSrc(): ?string { return $this->imageSrc; }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }
}
