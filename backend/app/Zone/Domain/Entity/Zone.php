<?php

declare(strict_types=1);

namespace App\Zone\Domain\Entity;

use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\ValueObject\ZoneName;

class Zone
{
    private function __construct(
        private Uuid $uuid,
        private ZoneName $name,
        private RestaurantId $restaurantId,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        ZoneName $name,
        int $restaurantId,
    ): self {
        return new self($uuid, $name, RestaurantId::create($restaurantId));
    }

    public static function fromPersistence(
        string $uuid,
        string $name,
        int $restaurantId,
    ): self {
        return new self(
            Uuid::create($uuid),
            ZoneName::create($name),
            RestaurantId::create($restaurantId),
        );
    }

    public function dddUpdate(ZoneName $name): void
    {
        $this->name = $name;
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function name(): ZoneName
    {
        return $this->name;
    }

    public function restaurantId(): int
    {
        return $this->restaurantId->getValue();
    }
}
