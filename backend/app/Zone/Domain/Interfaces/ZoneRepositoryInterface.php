<?php

declare(strict_types=1);

namespace App\Zone\Domain\Interfaces;

use App\Zone\Domain\Entity\Zone;

interface ZoneRepositoryInterface
{
    public function save(Zone $zone): void;

    public function findById(string $uuid, int $restaurantId): ?Zone;

    public function findAll(int $restaurantId): array;

    public function delete(string $id, int $restaurantId): void;
}
