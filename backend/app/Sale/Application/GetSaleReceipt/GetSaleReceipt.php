<?php

declare(strict_types=1);

namespace App\Sale\Application\GetSaleReceipt;

use App\Sale\Domain\Interfaces\SaleReportRepositoryInterface;
use App\Sale\Domain\ReadModel\SaleReceipt;

class GetSaleReceipt
{
    public function __construct(
        private SaleReportRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(int $restaurantId, string $saleUuid): ?SaleReceipt
    {
        return $this->saleRepository->findReceiptBySaleUuid($restaurantId, $saleUuid);
    }
}
