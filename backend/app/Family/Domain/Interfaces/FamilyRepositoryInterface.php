<?php

declare(strict_types=1);

namespace App\Family\Domain\Interfaces;

use App\Family\Domain\Entity\Family;

interface FamilyRepositoryInterface
{
    public function save(Family $family): void;

    public function findById(string $id, int $restaurantId): ?Family;

    public function findAll(int $restaurantId): array;

    public function delete(string $id, int $restaurantId): void;
}
