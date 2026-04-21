<?php

declare(strict_types=1);

namespace App\Sale\Application\GetSaleLines;

use App\Sale\Domain\Interfaces\SaleReportRepositoryInterface;

class GetSaleLines
{
    public function __construct(
        private SaleReportRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(int $restaurantId, string $saleUuid): array
    {
        return $this->saleRepository->findLinesBySaleUuid($restaurantId, $saleUuid);
    }
}
