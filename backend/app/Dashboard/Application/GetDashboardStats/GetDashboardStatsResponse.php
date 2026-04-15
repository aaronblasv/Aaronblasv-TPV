<?php

declare(strict_types=1);

namespace App\Dashboard\Application\GetDashboardStats;

final readonly class GetDashboardStatsResponse
{
    public function __construct(
        public array $stats,
        public array $salesThisMonth,
        public array $topProducts,
        public array $salesByDay,
    ) {}

    /**
     * @return array<string, array>
     */
    public function toArray(): array
    {
        return [
            'stats' => $this->stats,
            'sales_this_month' => $this->salesThisMonth,
            'top_products' => $this->topProducts,
            'sales_by_day' => $this->salesByDay,
        ];
    }
}
