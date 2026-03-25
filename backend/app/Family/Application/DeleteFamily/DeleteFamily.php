<?php

namespace App\Family\Application\DeleteFamily;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class DeleteFamily
{

    private FamilyRepositoryInterface $repository;

    public function __construct(FamilyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(string $uuid): void
    {
        $family = $this->repository->findbyId($uuid);

        if(!$family) {
            throw new \Exception('Family not found');
        }

        $this->repository->delete($uuid);
    }

}