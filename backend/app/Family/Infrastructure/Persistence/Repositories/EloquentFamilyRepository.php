<?php

namespace App\Family\Infrastructure\Persistence\Repositories;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Shared\Domain\ValueObject\Uuid;
use App\Family\Domain\ValueObject\FamilyName;

class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function findAll(): array
    {
        return EloquentFamily::all()->map(
            fn(EloquentFamily $family) => $this->toDomain($family)
        )->toArray();
    }

    private function toDomain(EloquentFamily $family): Family
    {
        return Family::dddCreate(
            Uuid::create($family->uuid),
            FamilyName::create($family->name),
            $family->active,
        );
    }

    public function save(Family $family): void
    {
        EloquentFamily::updateOrCreate(
            ['uuid' => $family->getUuid()->getValue()],
            [
                'name' => $family->getName()->getValue(),
                'active' => $family->isActive(),
                'restaurant_id' => 1,
            ]
        );
    }

    public function findById(string $id): ?Family
    {
        $family = EloquentFamily::where('uuid', $id)->first();

        return $family ? $this->toDomain($family) : null;
    }

    public function delete(string $id): void
    {
        EloquentFamily::where('uuid', $id)->delete();
    }


}