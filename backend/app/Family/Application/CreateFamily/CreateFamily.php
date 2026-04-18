<?php

declare(strict_types=1);

namespace App\Family\Application\CreateFamily;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Family\Domain\ValueObject\FamilyName;

class CreateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $repository,
    ) {}

    public function __invoke(string $name, bool $active, int $restaurantId): CreateFamilyResponse
    {
        $family = Family::dddCreate(
            Uuid::generate(),
            FamilyName::create($name),
            $active,
            $restaurantId,
        );

        $this->repository->save($family);

        return CreateFamilyResponse::create($family);
    }
}
