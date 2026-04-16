<?php

namespace App\Family\Application\DeactivateFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class DeactivateFamily
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

        $family->dddUpdate($family->name(), false);

        $this->repository->save($family);
    }
}
