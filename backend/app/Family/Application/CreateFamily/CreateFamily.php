<?php

namespace App\Family\Application\CreateFamily;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Application\CreateFamily\CreateFamilyResponse;
use App\Shared\Domain\ValueObject\Uuid;
use App\Family\Domain\ValueObject\FamilyName;

class CreateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $repository,
    ) {}

    public function __invoke(string $name, bool $active): CreateFamilyResponse
    {
        $family = Family::dddCreate(
            Uuid::generate(),
            FamilyName::create($name),
            $active,
        );

        $this->repository->save($family);

        return CreateFamilyResponse::create($family);
    }
}