<?php

namespace App\Table\Application\UpdateTable;

use App\Table\Domain\Exception\TableNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Domain\ValueObject\TableName;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateTable
{
    public function __construct(
        private TableRepositoryInterface $repository,
    ) {}

    public function __invoke(string $uuid, string $name, string $zoneId, int $restaurantId): UpdateTableResponse
    {
        $table = $this->repository->findById($uuid, $restaurantId);

        if ($table === null) {
            throw new TableNotFoundException($uuid);
        }

        $table->dddUpdate(TableName::create($name), Uuid::create($zoneId));

        $this->repository->save($table);

        return UpdateTableResponse::create($table);
    }
}
