<?php

declare(strict_types=1);

namespace App\CashShift\Application\GetCurrentCashShift;

use App\CashShift\Domain\Interfaces\CashShiftRepositoryInterface;
use App\CashShift\Domain\Interfaces\CashShiftSalesReadModelInterface;

class GetCurrentCashShift
{
    public function __construct(
        private CashShiftRepositoryInterface $repository,
        private CashShiftSalesReadModelInterface $salesReadModel,
    ) {}

    public function __invoke(int $restaurantId): ?GetCurrentCashShiftResponse
    {
        $cashShift = $this->repository->findOpenByRestaurant($restaurantId);
        if (!$cashShift) {
            return null;
        }

        $summary = $this->salesReadModel->getWindowSummary($restaurantId, $cashShift->openedAt(), null);

        return GetCurrentCashShiftResponse::create($cashShift, $summary);
    }
}