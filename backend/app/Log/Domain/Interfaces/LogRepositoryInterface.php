<?php

declare(strict_types=1);

namespace App\Log\Domain\Interfaces;

use App\Log\Domain\Entity\Log;

interface LogRepositoryInterface
{
    public function save(Log $log): void;

    public function findAll(int $restaurantId, int $limit = 50, int $offset = 0): array;

    public function findByUser(int $restaurantId, string $userId, int $limit = 50, int $offset = 0): array;

    public function findByAction(int $restaurantId, string $action, int $limit = 50, int $offset = 0): array;

    public function findByEntity(int $restaurantId, string $entityType, string $entityUuid, int $limit = 50, int $offset = 0): array;

    public function count(int $restaurantId): int;
}
