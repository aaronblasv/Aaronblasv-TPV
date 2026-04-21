<?php

declare(strict_types=1);

namespace App\Sale\Application\GetSalesReport;

use App\Sale\Domain\Interfaces\SaleReportRepositoryInterface;
use App\Sale\Domain\ReadModel\SalesGroupedReport;
use App\Shared\Domain\CacheRepositoryInterface;

class GetSalesReport
{
    public function __construct(
        private SaleReportRepositoryInterface $saleRepository,
        private CacheRepositoryInterface $cacheRepository,
    ) {}

    public function __invoke(int $restaurantId, ?string $from, ?string $to): SalesGroupedReport
    {
        $cacheKey = "sales_report:{$restaurantId}:{$from}:{$to}";

        return $this->cacheRepository->remember($cacheKey, 300, fn() =>
            $this->saleRepository->getGroupedReport($restaurantId, $from, $to)
        );
    }
}
