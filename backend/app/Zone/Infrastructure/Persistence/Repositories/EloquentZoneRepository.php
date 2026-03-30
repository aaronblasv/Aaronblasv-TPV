<?php

namespace App\Zone\Infrastructure\Persistence\Repositories;

use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;
use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;


class EloquentZoneRepository implements ZoneRepositoryInterface
{

    public function findAll(): array
    {
        return EloquentZone::all()->map(
            fn(EloquentZone $zone) => $this->toDomain($zone)
        )->toArray();
    }

    public function toDomain(EloquentZone $zone): Zone
    {
        return Zone::dddCreate(
            Uuid::create($zone->uuid),
            ZoneName::create($zone->name),
        );
    }

    public function save(Zone $zone): void
    {
        EloquentZone::updateOrCreate(
            ['uuid' => $zone->getUuid()->getValue()],
            [
                'name' => $zone->getName()->getValue(),
                'restaurant_id' => auth()->user()->restaurant_id,
            ]
        );
    }

    public function findById(string $id): ?Zone
    {
        $zone = EloquentZone::where('uuid', $id)->first();

        return $zone ? $this->toDomain($zone) : null;
    }

    public function delete(string $id): void
    {
        EloquentZone::where('uuid', $id)->delete();
    }
}