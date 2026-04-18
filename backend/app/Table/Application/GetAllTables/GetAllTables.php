<?php

declare(strict_types=1);

namespace App\Table\Application\GetAllTables;

use App\Table\Domain\Entity\Table;
use App\Table\Domain\Interfaces\TableRepositoryInterface;

class GetAllTables
{
    public function __construct(
        private TableRepositoryInterface $repository,
    ) {}

    public function __invoke(int $restaurantId): array
    {
        $tables = $this->repository->findAll($restaurantId);

        return array_map(
            fn(Table $table) => GetAllTablesResponse::create($table),
            $tables
        );
    }
}
