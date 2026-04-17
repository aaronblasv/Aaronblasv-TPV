<?php

declare(strict_types=1);

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;

interface SaleRepositoryInterface
{
    public function save(Sale $sale): void;
    public function saveLine(SaleLine $line): void;
    public function update(Sale $sale): void;
    public function updateLine(SaleLine $line): void;
    public function getNextTicketNumber(int $restaurantId): int;
    public function findAll(int $restaurantId): array;
    public function findByUuid(int $restaurantId, string $saleUuid): ?Sale;
    public function findDomainLinesBySaleUuid(int $restaurantId, string $saleUuid): array;

    /** @return array<int, array<string, mixed>> */
    public function findFiltered(int $restaurantId, ?string $from, ?string $to): array;

    /** @return array<int, array<string, mixed>> */
    public function findLinesBySaleUuid(int $restaurantId, string $saleUuid): array;

    /** @return array{by_day: array, by_zone: array, by_product: array, by_user: array} */
    public function getGroupedReport(int $restaurantId, ?string $from, ?string $to): array;
}