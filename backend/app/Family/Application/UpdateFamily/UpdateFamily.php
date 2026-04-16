<?php

namespace App\Family\Application\UpdateFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;

class UpdateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $repository,
    ) {}

    public function __invoke(string $uuid, string $name, bool $active, int $restaurantId): UpdateFamilyResponse
    {
        $family = $this->repository->findById($uuid, $restaurantId);

        if ($family === null) {
            throw new FamilyNotFoundException($uuid);
        }

        $family->dddUpdate(FamilyName::create($name), $active);

        $this->repository->save($family);

        return UpdateFamilyResponse::create($family);
    }
}
