<?php

declare(strict_types=1);

namespace App\Tax\Application\GetAllTaxes;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class GetAllTaxes
{
    public function __construct(
        private TaxRepositoryInterface $repository,
    ) {}

    public function __invoke(int $restaurantId): array
    {
        $taxes = $this->repository->findAll($restaurantId);

        return array_map(
            fn(Tax $tax) => GetAllTaxesResponse::create($tax),
            $taxes
        );
    }
}
