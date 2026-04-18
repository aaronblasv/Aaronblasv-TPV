<?php

declare(strict_types=1);

namespace App\Family\Domain\Entity;

use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;
use App\Family\Domain\ValueObject\FamilyName;

class Family
{
    private function __construct(
        private Uuid $uuid,
        private FamilyName $name,
        private bool $active,
        private RestaurantId $restaurantId,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        FamilyName $name,
        bool $active,
        int $restaurantId,
    ): self {
        return new self($uuid, $name, $active, RestaurantId::create($restaurantId));
    }

    public static function fromPersistence(
        string $uuid,
        string $name,
        bool $active,
        int $restaurantId,
    ): self {
        return new self(
            Uuid::create($uuid),
            FamilyName::create($name),
            $active,
            RestaurantId::create($restaurantId),
        );
    }

    public function dddUpdate(FamilyName $name, bool $active): void
    {
        $this->name = $name;
        $this->active = $active;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function name(): FamilyName
    {
        return $this->name;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function restaurantId(): int
    {
        return $this->restaurantId->getValue();
    }
}
