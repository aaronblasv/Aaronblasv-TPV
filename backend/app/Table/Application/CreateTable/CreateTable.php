<?php

declare(strict_types=1);

namespace App\Table\Application\CreateTable;

use App\Table\Domain\Entity\Table;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Domain\ValueObject\TableName;
use App\Shared\Domain\ValueObject\Uuid;

class CreateTable
{
    public function __construct(
        private TableRepositoryInterface $repository,
    ) {}

    public function __invoke(string $name, string $zoneId, int $restaurantId): CreateTableResponse
    {
        $table = Table::dddCreate(
            Uuid::generate(),
            TableName::create($name),
            Uuid::create($zoneId),
            $restaurantId,
        );

        $this->repository->save($table);

        return CreateTableResponse::create($table);
    }
}
