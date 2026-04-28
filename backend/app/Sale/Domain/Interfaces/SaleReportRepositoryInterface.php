<?php

declare(strict_types=1);

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\ReadModel\SaleLineDetail;
use App\Sale\Domain\ReadModel\SaleReceipt;
use App\Sale\Domain\ReadModel\SalesGroupedReport;
use App\Sale\Domain\ReadModel\SaleSummary;

interface SaleReportRepositoryInterface
{
    /** @return SaleSummary[] */
    public function findFiltered(int $restaurantId, ?string $from, ?string $to): array;

    /** @return SaleLineDetail[] */
    public function findLinesBySaleUuid(int $restaurantId, string $saleUuid): array;

    public function findReceiptBySaleUuid(int $restaurantId, string $saleUuid): ?SaleReceipt;

    public function getGroupedReport(int $restaurantId, ?string $from, ?string $to): SalesGroupedReport;
}