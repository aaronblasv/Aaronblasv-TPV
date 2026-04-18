<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Persistence\Repositories;

use App\Table\Domain\Entity\Table;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;

class EloquentTableRepository implements TableRepositoryInterface
{
    public function __construct(
        private EloquentTable $model,
        private EloquentZone $zoneModel,
    ) {}

    public function findAll(int $restaurantId): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(fn(EloquentTable $table) => $this->toDomain($table))
            ->toArray();
    }

    public function save(Table $table): void
    {
        $zone = $this->zoneModel->newQuery()
            ->where('uuid', $table->zoneId()->getValue())
            ->firstOrFail();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $table->uuid()->getValue()],
            [
                'name' => $table->name()->getValue(),
                'zone_id' => $zone->id,
                'restaurant_id' => $table->restaurantId(),
                'merged_with' => $table->mergedWith()?->getValue(),
            ]
        );
    }

    public function findById(string $id, int $restaurantId): ?Table
    {
        $table = $this->model->newQuery()
            ->where('uuid', $id)
            ->where('restaurant_id', $restaurantId)
            ->first();

        return $table ? $this->toDomain($table) : null;
    }

    public function delete(string $id, int $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id)
            ->where('restaurant_id', $restaurantId)
            ->delete();
    }

    public function update(Table $table): void
    {
        $zone = $this->zoneModel->newQuery()
            ->where('uuid', $table->zoneId()->getValue())
            ->firstOrFail();

        $this->model->newQuery()
            ->where('uuid', $table->uuid()->getValue())
            ->update([
                'name' => $table->name()->getValue(),
                'zone_id' => $zone->id,
                'merged_with' => $table->mergedWith()?->getValue(),
            ]);
    }

    public function findByMergedWith(string $parentUuid, int $restaurantId): array
    {
        return $this->model->newQuery()
            ->where('merged_with', $parentUuid)
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(fn(EloquentTable $table) => $this->toDomain($table))
            ->toArray();
    }

    private function toDomain(EloquentTable $table): Table
    {
        $zone = $this->zoneModel->newQuery()->find($table->zone_id);

        return Table::fromPersistence(
            $table->uuid,
            $table->name,
            $zone->uuid,
            $table->restaurant_id,
            $table->merged_with,
        );
    }
}
