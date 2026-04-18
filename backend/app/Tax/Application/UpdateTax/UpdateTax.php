<?php

declare(strict_types=1);

namespace App\Tax\Application\UpdateTax;

use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class UpdateTax
{
    public function __construct(
        private TaxRepositoryInterface $repository,
    ) {}

    public function __invoke(string $uuid, string $name, int $percentage, int $restaurantId): UpdateTaxResponse
    {
        $tax = $this->repository->findById($uuid, $restaurantId);

        if ($tax === null) {
            throw new TaxNotFoundException($uuid);
        }

        $tax->dddUpdate(
            TaxName::create($name),
            TaxPercentage::create($percentage),
        );

        $this->repository->save($tax);

        return UpdateTaxResponse::create($tax);
    }
}
