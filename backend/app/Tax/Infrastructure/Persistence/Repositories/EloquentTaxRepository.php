<?php

namespace App\Tax\Infrastructure\Persistence\Repositories;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class EloquentTaxRepository implements TaxRepositoryInterface
{
    public function findAll(): array
    {
        return EloquentTax::all()->map(
            fn(EloquentTax $tax) => $this->toDomain($tax)
        )->toArray();
    }

    private function toDomain(EloquentTax $tax): Tax
    {
        return Tax::dddCreate(
            Uuid::create($tax->uuid),
            TaxName::create($tax->name),
            TaxPercentage::create($tax->percentage),
        );
    }

    public function save(Tax $tax): void
    {
        EloquentTax::updateOrCreate(
            ['uuid' => $tax->getUuid()->getValue()],
            [
                'name' => $tax->getName()->getValue(),
                'percentage' => $tax->getPercentage()->getValue(),
            ]
        );
    }

    public function findById(string $id): ?Tax
    {
        $tax = EloquentTax::where('uuid', $id)->first();

        return $tax ? $this->toDomain($tax) : null;
    }

    public function delete(string $id): void
    {
        EloquentTax::where('uuid', $id)->delete();
    }
}