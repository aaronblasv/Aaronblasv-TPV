<?php

declare(strict_types=1);

namespace App\Tax\Infrastructure\Persistence\Repositories;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;

class EloquentTaxRepository implements TaxRepositoryInterface
{
    public function __construct(
        private EloquentTax $model,
    ) {}

    public function findAll(int $restaurantId): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(fn (EloquentTax $tax) => $this->toDomain($tax))
            ->toArray();
    }

    public function save(Tax $tax): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $tax->uuid()->getValue()],
            [
                'name' => $tax->name()->getValue(),
                'percentage' => $tax->percentage()->getValue(),
                'restaurant_id' => $tax->restaurantId(),
            ]
        );
    }

    public function existsByName(string $name, int $restaurantId, ?string $excludeUuid = null): bool
    {
        $query = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))]);

        if ($excludeUuid !== null) {
            $query->where('uuid', '!=', $excludeUuid);
        }

        return $query->exists();
    }

    public function findById(string $id, int $restaurantId): ?Tax
    {
        $tax = $this->model->newQuery()
            ->where('uuid', $id)
            ->where('restaurant_id', $restaurantId)
            ->first();

        return $tax ? $this->toDomain($tax) : null;
    }

    public function delete(string $id, int $restaurantId): void
    {
        $this->model->newQuery()
            ->where('uuid', $id)
            ->where('restaurant_id', $restaurantId)
            ->delete();
    }

    private function toDomain(EloquentTax $tax): Tax
    {
        return Tax::fromPersistence(
            $tax->uuid,
            $tax->name,
            $tax->percentage,
            $tax->restaurant_id,
        );
    }
}
