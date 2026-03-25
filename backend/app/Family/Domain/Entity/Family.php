<?php

namespace App\Family\Domain\Entity;

use App\Shared\Domain\ValueObject\Uuid;
use App\Family\Domain\ValueObject\FamilyName;

class Family
{
    private function __construct(
        private Uuid $uuid,
        private FamilyName $name,
        private bool $active,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        FamilyName $name,
        bool $active,
    ) : self {
        return new self($uuid, $name, $active);
    }

    public function dddUpdate(FamilyName $name, bool $active): void
    {
        $this->name = $name;
        $this->active = $active;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getName(): FamilyName
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}