<?php

declare(strict_types=1);

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\Entity\OrderLine;
use App\Shared\Domain\ValueObject\DomainDateTime;

interface OrderLineRepositoryInterface
{
    public function save(OrderLine $line): void;

    public function findById(string $uuid, int $restaurantId): ?OrderLine;

    public function findAllByOrderId(string $orderUuid, int $restaurantId): array;

    public function findAllByIdsForUpdate(array $lineUuids, string $orderUuid, int $restaurantId): array;

    public function findAllByOrderIds(array $orderUuids, int $restaurantId): array;

    public function bulkMarkSentToKitchen(array $lineUuids, int $restaurantId, DomainDateTime $sentAt): void;

    public function update(OrderLine $line): void;

    public function delete(string $uuid, int $restaurantId): void;
}
