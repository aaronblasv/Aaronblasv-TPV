<?php

declare(strict_types=1);

namespace App\Sale\Application\GetAllSales;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

class GetAllSales
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(int $restaurantId, ?string $from, ?string $to): array
    {
        return $this->saleRepository->findFiltered($restaurantId, $from, $to);
    }
}
