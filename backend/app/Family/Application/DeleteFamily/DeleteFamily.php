<?php

namespace App\Family\Application\DeleteFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class DeleteFamily
{
    public function __construct(
        private FamilyRepositoryInterface $repository,
    ) {}

    public function __invoke(string $uuid, int $restaurantId): void
    {
        $family = $this->repository->findById($uuid, $restaurantId);

        if ($family === null) {
            throw new FamilyNotFoundException($uuid);
        }

        $this->repository->delete($uuid, $restaurantId);
    }
}
