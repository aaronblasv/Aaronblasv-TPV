<?php

declare(strict_types=1);

namespace App\Table\Application\MergeTables;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
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
        private TransactionManagerInterface $transactionManager,
    ) {}

    /**
     * @param  string[]  $childTableUuids
     */
    public function __invoke(string $parentTableUuid, array $childTableUuids, int $restaurantId): void
    {
        $this->transactionManager->run(function () use ($parentTableUuid, $childTableUuids, $restaurantId) {
            if ($childTableUuids === []) {
                throw new InvalidTableMergeException('Selecciona al menos una mesa vacía para unir.');
            }

            $parent = $this->repository->findById($parentTableUuid, $restaurantId);
            if (! $parent) {
                throw new TableNotFoundException($parentTableUuid);
            }

            if ($parent->isMerged() || $this->repository->findByMergedWith($parentTableUuid, $restaurantId) !== []) {
                throw new InvalidTableMergeException('No se pueden volver a unir mesas que ya forman parte de una agrupación.');
            }

            if ($this->orderRepository->findOpenByTableId($parentTableUuid, $restaurantId) === null) {
                throw new InvalidTableMergeException('La mesa principal debe tener un pedido abierto para poder unir otras mesas vacías.');
            }

            foreach ($childTableUuids as $childUuid) {
                if ($childUuid === $parentTableUuid) {
                    throw new InvalidTableMergeException('La mesa principal no puede unirse consigo misma.');
                }

                $child = $this->repository->findById($childUuid, $restaurantId);
                if (! $child) {
                    throw new TableNotFoundException($childUuid);
                }

                if ($child->isMerged() || $this->repository->findByMergedWith($childUuid, $restaurantId) !== []) {
                    throw new InvalidTableMergeException('No se pueden volver a unir mesas que ya forman parte de una agrupación.');
                }

                if ($this->orderRepository->findOpenByTableId($childUuid, $restaurantId) !== null) {
                    throw new InvalidTableMergeException('No se pueden unir dos o más mesas con pedido abierto. Solo una mesa puede tener pedido y el resto deben estar vacías.');
                }

                $child->mergeWith(Uuid::create($parentTableUuid));
                $this->repository->update($child);
            }
        });
    }
}
