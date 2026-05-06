<?php

declare(strict_types=1);

namespace App\Order\Application\UpdateOrderLineQuantity;

use App\Order\Domain\Exception\CannotModifyPaidOrderLineException;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\ValueObject\Quantity;

class UpdateOrderLineQuantity
{
    public function __construct(
        private OrderLineRepositoryInterface $repository,
    ) {}

    public function __invoke(string $lineUuid, int $quantity, int $restaurantId): void
    {
        $line = $this->repository->findById($lineUuid, $restaurantId);
        if (!$line) {
            throw new OrderLineNotFoundException($lineUuid);
        }

        if ($line->isPaid()) {
            throw new CannotModifyPaidOrderLineException($lineUuid);
        }

        $line->updateQuantity(Quantity::create($quantity));
        $this->repository->update($line);
    }
}
