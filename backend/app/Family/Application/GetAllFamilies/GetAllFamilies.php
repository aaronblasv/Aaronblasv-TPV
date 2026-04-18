<?php

declare(strict_types=1);

namespace App\Family\Application\GetAllFamilies;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class GetAllFamilies
{
    public function __construct(
        private FamilyRepositoryInterface $repository,
    ) {}

    public function __invoke(int $restaurantId): array
    {
        $families = $this->repository->findAll($restaurantId);

        return array_map(
            fn(Family $family) => GetAllFamiliesResponse::create($family),
            $families
        );
    }
}
