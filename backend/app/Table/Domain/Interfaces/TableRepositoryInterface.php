<?php

declare(strict_types=1);

namespace App\Table\Domain\Interfaces;

use App\Table\Domain\Entity\Table;

interface TableRepositoryInterface
{
    public function save(Table $table): void;

    public function update(Table $table): void;

    public function findById(string $id, int $restaurantId): ?Table;

    /** @return Table[] */
    public function findAll(int $restaurantId): array;

    /** @return Table[] */
    public function findByMergedWith(string $parentUuid, int $restaurantId): array;

    public function delete(string $id, int $restaurantId): void;
}
