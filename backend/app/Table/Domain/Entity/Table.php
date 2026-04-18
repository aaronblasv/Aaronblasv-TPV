<?php

declare(strict_types=1);

namespace App\Table\Domain\Entity;

use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class Table
{
    private function __construct(
        private Uuid $uuid,
        private TableName $name,
        private Uuid $zoneId,
        private RestaurantId $restaurantId,
        private ?Uuid $mergedWith = null,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        TableName $name,
        Uuid $zoneId,
        int $restaurantId,
    ): self {
        return new self($uuid, $name, $zoneId, RestaurantId::create($restaurantId));
    }

    public static function fromPersistence(
        string $uuid,
        string $name,
        string $zoneId,
        int $restaurantId,
        ?string $mergedWith = null,
    ): self {
        return new self(
            Uuid::create($uuid),
            TableName::create($name),
            Uuid::create($zoneId),
            RestaurantId::create($restaurantId),
            $mergedWith ? Uuid::create($mergedWith) : null,
        );
    }

    public function dddUpdate(TableName $name, Uuid $zoneId): void
    {
        $this->name = $name;
        $this->zoneId = $zoneId;
    }

    public function mergeWith(Uuid $parentTableUuid): void
    {
        $this->mergedWith = $parentTableUuid;
    }

    public function unmerge(): void
    {
        $this->mergedWith = null;
    }

    public function isMerged(): bool
    {
        return $this->mergedWith !== null;
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function name(): TableName { return $this->name; }
    public function zoneId(): Uuid { return $this->zoneId; }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function mergedWith(): ?Uuid { return $this->mergedWith; }
}
