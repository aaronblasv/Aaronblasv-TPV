<?php

namespace App\Family\Application\UpdateFamily;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Application\UpdateFamily\UpdateFamilyResponse;
use App\Shared\Domain\ValueObject\Uuid;
use App\Family\Domain\ValueObject\FamilyName;

class UpdateFamily
{

    private FamilyRepositoryInterface $repository;

    public function __construct(FamilyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(string $uuid, string $name, bool $active): UpdateFamilyResponse
    {

        $family = $this->repository->findbyId($uuid);

        if(!$family) {
            throw new \Exception('Family not found');
        }

        $family->dddUpdate(
            FamilyName::create($name),
            $active,
        );

        $this->repository->save($family);

        return UpdateFamilyResponse::create($family);

    }

}