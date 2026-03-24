<?php

namespace App\Tax\Application\GetAllTaxes;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Application\GetAllTaxes\GetAllTaxesResponse;

class GetAllTaxes
{
    public function __construct(
        private TaxRepositoryInterface $repository,
    ) {}

    public function __invoke(): array
    {
        $taxes = $this->repository->findAll();

        return array_map(
            fn(Tax $tax) => GetAllTaxesResponse::create($tax),
            $taxes
        );
    }
}