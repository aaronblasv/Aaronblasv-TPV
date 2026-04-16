<?php

declare(strict_types=1);

namespace App\Order\Application\RemoveOrderLine;

use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;

class RemoveOrderLine
{
    public function __construct(
        private OrderLineRepositoryInterface $repository,
    ) {}

    public function __invoke(string $lineUuid, int $restaurantId): void
    {
        $line = $this->repository->findById($lineUuid, $restaurantId);
        if (!$line) {
            throw new OrderLineNotFoundException($lineUuid);
        }

        $this->repository->delete($lineUuid, $restaurantId);
    }
}
