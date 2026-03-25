<?php

namespace App\Family\Application\GetAllFamilies;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Application\GetAllFamilies\GetAllFamiliesResponse;

class GetAllFamilies
{

    public function __construct(
        private FamilyRepositoryInterface $repository,
    ) {}

    public function __invoke(): array
    {
        $families = $this->repository->findAll();

        return array_map(
            fn(Family $family) => GetAllFamiliesResponse::create($family),
            $families
        );
    }

    

}