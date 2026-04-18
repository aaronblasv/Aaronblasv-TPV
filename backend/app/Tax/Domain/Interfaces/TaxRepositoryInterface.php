<?php

declare(strict_types=1);

namespace App\Tax\Domain\Interfaces;

use App\Tax\Domain\Entity\Tax;

interface TaxRepositoryInterface
{
    public function save(Tax $tax): void;

    public function findById(string $id, int $restaurantId): ?Tax;

    public function findAll(int $restaurantId): array;

    public function delete(string $id, int $restaurantId): void;
}
