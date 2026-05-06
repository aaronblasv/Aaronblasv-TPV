<?php

declare(strict_types=1);

namespace App\Table\Application\UnmergeTables;

use App\Table\Domain\Exception\InvalidTableMergeException;
use App\Table\Domain\Exception\TableNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;

class UnmergeTables
{
    public function __construct(
        private TableRepositoryInterface $repository,
    ) {}

    public function __invoke(string $parentTableUuid, int $restaurantId): void
    {
        $parent = $this->repository->findById($parentTableUuid, $restaurantId);
        if (! $parent) {
            throw new TableNotFoundException($parentTableUuid);
        }

        if ($parent->isMerged()) {
            throw new InvalidTableMergeException('Solo se puede desagrupar desde la mesa principal.');
        }

        $children = $this->repository->findByMergedWith($parentTableUuid, $restaurantId);

        if ($children === []) {
            throw new InvalidTableMergeException('La mesa indicada no tiene mesas agrupadas.');
        }

        foreach ($children as $child) {
            $child->unmerge();
            $this->repository->update($child);
        }
    }
}
