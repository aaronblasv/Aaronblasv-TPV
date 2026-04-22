<?php

declare(strict_types=1);

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\Entity\Sale;

interface SaleReadRepositoryInterface
{
    public function findAll(int $restaurantId): array;
    public function findByUuid(int $restaurantId, string $saleUuid): ?Sale;
    public function findDomainLinesBySaleUuid(int $restaurantId, string $saleUuid): array;
    public function findDomainLinesBySaleUuidForUpdate(int $restaurantId, string $saleUuid, array $lineUuids = []): array;
}