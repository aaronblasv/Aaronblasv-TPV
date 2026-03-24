<?php

namespace App\Tax\Domain\Interfaces;

use App\Tax\Domain\Entity\Tax;

interface TaxRepositoryInterface
{
    public function save(Tax $tax): void;
    public function findById(string $id): ?Tax;
    public function findAll(): array;
    public function delete(string $id): void;
}