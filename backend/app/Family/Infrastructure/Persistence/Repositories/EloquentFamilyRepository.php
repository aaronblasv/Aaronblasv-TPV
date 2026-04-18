<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Persistence\Repositories;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;

class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function __construct(
        private EloquentFamily $model,
    ) {}

    public function findAll(int $restaurantId): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(fn(EloquentFamily $family) => $this->toDomain($family))
            ->toArray();
    }

    public function save(Family $family): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $family->uuid()->getValue()],
            [
                'name' => $family->name()->getValue(),
                'active' => $family->active(),
                'restaurant_id' => $family->restaurantId(),
            ]
        );
    }

    public function findById(string $id, int $restaurantId): ?Family
    {
        $family = $this->model->newQuery()
            ->where('uuid', $id)
            ->where('restaurant_id', $restaurantId)
            ->first();

        return $family ? $this->toDomain($family) : null;
    }

    public function delete(string $id, int $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id)
            ->where('restaurant_id', $restaurantId)
            ->delete();
    }

    private function toDomain(EloquentFamily $family): Family
    {
        return Family::fromPersistence(
            $family->uuid,
            $family->name,
            (bool) $family->active,
            $family->restaurant_id,
        );
    }
}
