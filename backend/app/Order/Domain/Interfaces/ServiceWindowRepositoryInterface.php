<?php

declare(strict_types=1);

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\Entity\ServiceWindow;

interface ServiceWindowRepositoryInterface
{
    public function nextWindowNumberForOrder(string $orderUuid, int $restaurantId): int;

    public function save(ServiceWindow $serviceWindow): void;
}
