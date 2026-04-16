<?php

declare(strict_types=1);

namespace App\Sale\Application\GetSalesReport;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

class GetSalesReport
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(int $restaurantId, ?string $from, ?string $to): array
    {
        return $this->saleRepository->getGroupedReport($restaurantId, $from, $to);
    }
}
