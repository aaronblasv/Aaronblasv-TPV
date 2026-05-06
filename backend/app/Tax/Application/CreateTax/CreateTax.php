<?php

declare(strict_types=1);

namespace App\Tax\Application\CreateTax;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class CreateTax
{
    public function __construct(
        private TaxRepositoryInterface $repository,
    ) {}

    public function __invoke(string $name, float $percentage, int $restaurantId): CreateTaxResponse
    {
        $tax = Tax::dddCreate(
            Uuid::generate(),
            TaxName::create($name),
            TaxPercentage::fromPercentage($percentage),
            $restaurantId,
        );

        $this->repository->save($tax);

        return CreateTaxResponse::create($tax);
    }
}
