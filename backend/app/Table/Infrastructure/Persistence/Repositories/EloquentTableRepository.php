<?php

namespace App\Table\Infrastructure\Persistence\Repositories;

use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Domain\Entity\Table;
use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\ValueObject\TableName;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;

class EloquentTableRepository implements TableRepositoryInterface
{
    public function findAll(): array
    {
        return EloquentTable::all()->map(
            fn(EloquentTable $table) => $this->toDomain($table)
        )->toArray();
    }

    private function toDomain(EloquentTable $table): Table
    {
        $zone = EloquentZone::find($table->zone_id);

        return Table::dddCreate(
            Uuid::create($table->uuid),
            TableName::create($table->name),
            $zone->uuid,
        );
    }

    public function save(Table $table): void
    {
        $zone = EloquentZone::where('uuid', $table->getZoneId())->first();

        EloquentTable::updateOrCreate(
            ['uuid' => $table->getUuid()->getValue()],
            [
                'name' => $table->getName()->getValue(),
                'zone_id' => $zone->id,
                'restaurant_id' => auth()->user()->restaurant_id,
            ]
        );
    }

    public function findById(string $id): ?Table
    {
        $table = EloquentTable::where('uuid', $id)->first();

        return $table ? $this->toDomain($table) : null;
    }

    public function delete(string $id): void
    {
        EloquentTable::where('uuid', $id)->delete();
    }
}