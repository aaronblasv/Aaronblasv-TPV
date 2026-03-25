<?php

namespace App\Family\Application\CreateFamily;

use App\Family\Domain\Entity\Family;

final readonly class CreateFamilyResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public bool $active,
    ) {}

    public static function create(Family $family): self
    {
        return new self(
            $family->getUuid()->getValue(),
            $family->getName()->getValue(),
            $family->isActive()
        );
    }
}