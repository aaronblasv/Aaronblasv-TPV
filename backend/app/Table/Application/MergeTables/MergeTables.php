<?php

declare(strict_types=1);

namespace App\Table\Application\MergeTables;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\ValueObject\Diners;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Exception\InvalidTableMergeException;
use App\Table\Domain\Exception\TableNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;

class MergeTables
{
    public function __construct(
        private TableRepositoryInterface $repository,
        private OrderRepositoryInterface $orderRepository,
        private OrderLineRepositoryInterface $orderLineRepository,
        private TransactionManagerInterface $transactionManager,
    ) {}

    /**
     * @param string[] $childTableUuids
     */
    public function __invoke(string $parentTableUuid, array $childTableUuids, int $restaurantId): void
    {
        $this->transactionManager->run(function () use ($parentTableUuid, $childTableUuids, $restaurantId) {
            $parent = $this->repository->findById($parentTableUuid, $restaurantId);
            if (!$parent) {
                throw new TableNotFoundException($parentTableUuid);
            }

            if ($this->orderRepository->findOpenByTableId($parentTableUuid, $restaurantId) === null) {
                throw new InvalidTableMergeException('La mesa principal debe tener un pedido abierto para poder unir otras mesas vacías.');
            }

            foreach ($childTableUuids as $childUuid) {
                $child = $this->repository->findById($childUuid, $restaurantId);
                if (!$child) {
                    throw new TableNotFoundException($childUuid);
                }

                if ($this->orderRepository->findOpenByTableId($childUuid, $restaurantId) !== null) {
                    throw new InvalidTableMergeException('No se pueden unir dos o más mesas con pedido abierto. Solo una mesa puede tener pedido y el resto deben estar vacías.');
                }

                $child->mergeWith(Uuid::create($parentTableUuid));
                $this->repository->update($child);
            }

            $this->mergeOpenOrders($parentTableUuid, $childTableUuids, $restaurantId);
        });
    }

    /**
     * @param string[] $childTableUuids
     */
    private function mergeOpenOrders(string $parentTableUuid, array $childTableUuids, int $restaurantId): void
    {
        $survivorOrder = $this->orderRepository->findOpenByTableId($parentTableUuid, $restaurantId);

        if (!$survivorOrder) {
            return;
        }

        $totalDiners = $survivorOrder->diners()->getValue();
        $totalOrderDiscount = $survivorOrder->discountAmount();
        $childOrders = [];

        foreach ($childTableUuids as $childTableUuid) {
            $childOrder = $this->orderRepository->findOpenByTableId($childTableUuid, $restaurantId);
            if ($childOrder) {
                $childOrders[] = $childOrder;
            }
        }

        foreach ($childOrders as $childOrder) {
            $totalDiners += $childOrder->diners()->getValue();
            $totalOrderDiscount += $childOrder->discountAmount();

            foreach ($this->orderLineRepository->findAllByOrderId($childOrder->uuid()->getValue(), $restaurantId) as $line) {
                $line->moveToOrder($survivorOrder->uuid());
                $this->orderLineRepository->update($line);
            }

            $this->orderRepository->delete($childOrder->uuid()->getValue(), $restaurantId);
        }

        $survivorOrder->updateDiners(Diners::create($totalDiners));

        $mergedLines = $this->orderLineRepository->findAllByOrderId($survivorOrder->uuid()->getValue(), $restaurantId);
        $linesSubtotal = array_reduce(
            $mergedLines,
            static fn(int $sum, OrderLine $line) => $sum + $line->subtotalAfterDiscount(),
            0,
        );

        $survivorOrder->applyDiscount(
            $totalOrderDiscount > 0 ? 'amount' : null,
            $totalOrderDiscount,
            $linesSubtotal,
        );

        $this->orderRepository->update($survivorOrder);
    }
}
