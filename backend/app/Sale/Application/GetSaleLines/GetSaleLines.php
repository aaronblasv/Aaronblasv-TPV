<?php

declare(strict_types=1);

namespace App\Sale\Application\GetSaleLines;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

class GetSaleLines
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(int $restaurantId, string $saleUuid): array
    {
        return $this->saleRepository->findLinesBySaleUuid($restaurantId, $saleUuid);
    }
}
