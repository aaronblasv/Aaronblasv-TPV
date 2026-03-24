<?php

namespace App\Tax\Domain\Entity;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class Tax 
{
    private function __construct(
        private Uuid $uuid,
        private TaxName $name,
        private TaxPercentage $percentage,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        TaxName $name,
        TaxPercentage $percentage,
    ): self {
        return new self($uuid, $name, $percentage);
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getName(): TaxName
    {
        return $this->name;
    }

    public function getPercentage(): TaxPercentage
    {
        return $this->percentage;
    }
}