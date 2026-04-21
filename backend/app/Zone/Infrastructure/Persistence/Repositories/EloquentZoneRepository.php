<?php

declare(strict_types=1);

namespace App\Zone\Infrastructure\Persistence\Repositories;

use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;

class EloquentZoneRepository implements ZoneRepositoryInterface
{
    public function __construct(
        private EloquentZone $model,
    ) {}

    public function findAll(int $restaurantId): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(fn(EloquentZone $zone) => $this->toDomain($zone))
            ->toArray();
    }

    public function save(Zone $zone): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $zone->uuid()->getValue()],
            [
                'name' => $zone->name()->getValue(),
                'restaurant_id' => $zone->restaurantId(),
            ]
        );
    }

    public function findById(string $zoneUuid, int $restaurantId): ?Zone
    {
        $zone = $this->model->newQuery()
            ->where('uuid', $zoneUuid)
            ->where('restaurant_id', $restaurantId)
            ->first();

        return $zone ? $this->toDomain($zone) : null;
    }

    public function delete(string $zoneUuid, int $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $zoneUuid)
            ->where('restaurant_id', $restaurantId)
            ->delete();
    }

    private function toDomain(EloquentZone $zone): Zone
    {
        return Zone::fromPersistence(
            $zone->uuid,
            $zone->name,
            $zone->restaurant_id,
        );
    }
}
