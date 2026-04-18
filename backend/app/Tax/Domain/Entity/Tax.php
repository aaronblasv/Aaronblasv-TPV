<?php

declare(strict_types=1);

namespace App\Tax\Domain\Entity;

use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class Tax
{
    private function __construct(
        private Uuid $uuid,
        private TaxName $name,
        private TaxPercentage $percentage,
        private RestaurantId $restaurantId,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        TaxName $name,
        TaxPercentage $percentage,
        int $restaurantId,
    ): self {
        return new self($uuid, $name, $percentage, RestaurantId::create($restaurantId));
    }

    public static function fromPersistence(
        string $uuid,
        string $name,
        int $percentage,
        int $restaurantId,
    ): self {
        return new self(
            Uuid::create($uuid),
            TaxName::create($name),
            TaxPercentage::create($percentage),
            RestaurantId::create($restaurantId),
        );
    }

    public function dddUpdate(TaxName $name, TaxPercentage $percentage): void
    {
        $this->name = $name;
        $this->percentage = $percentage;
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function name(): TaxName
    {
        return $this->name;
    }

    public function percentage(): TaxPercentage
    {
        return $this->percentage;
    }

    public function restaurantId(): int
    {
        return $this->restaurantId->getValue();
    }
}
