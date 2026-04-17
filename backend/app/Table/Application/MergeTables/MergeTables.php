<?php

declare(strict_types=1);

namespace App\Table\Application\MergeTables;

use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Exception\TableNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;

class MergeTables
{
    public function __construct(
        private TableRepositoryInterface $repository,
    ) {}

    /**
     * @param string[] $childTableUuids
     */
    public function __invoke(string $parentTableUuid, array $childTableUuids, int $restaurantId): void
    {
        $parent = $this->repository->findById($parentTableUuid, $restaurantId);
        if (!$parent) {
            throw new TableNotFoundException($parentTableUuid);
        }

        foreach ($childTableUuids as $childUuid) {
            $child = $this->repository->findById($childUuid, $restaurantId);
            if (!$child) {
                throw new TableNotFoundException($childUuid);
            }

            $child->mergeWith(Uuid::create($parentTableUuid));
            $this->repository->update($child);
        }
    }
}
