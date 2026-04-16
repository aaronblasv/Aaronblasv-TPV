<?php

declare(strict_types=1);

namespace App\Order\Application\OpenOrder;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\TableAlreadyHasOpenOrderException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\Diners;
use App\Shared\Domain\ValueObject\Uuid;

class OpenOrder
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    public function __invoke(
        int $restaurantId,
        string $tableUuid,
        string $openedByUserUuid,
        int $diners,
    ): OpenOrderResponse {
        $existing = $this->repository->findOpenByTableId($tableUuid, $restaurantId);
        if ($existing) {
            throw new TableAlreadyHasOpenOrderException($tableUuid);
        }

        $order = Order::dddCreate(
            Uuid::generate(),
            $restaurantId,
            Uuid::create($tableUuid),
            Uuid::create($openedByUserUuid),
            Diners::create($diners),
        );

        $this->repository->save($order);

        return OpenOrderResponse::create($order);
    }
}