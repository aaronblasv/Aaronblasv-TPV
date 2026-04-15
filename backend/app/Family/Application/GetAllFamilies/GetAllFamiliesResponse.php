<?php

namespace App\Family\Application\GetAllFamilies;

use App\Family\Domain\Entity\Family;

final readonly class GetAllFamiliesResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public bool $active,
    ) {}

    public static function create(Family $family): self
    {
        return new self(
            $family->uuid()->getValue(),
            $family->name()->getValue(),
            $family->active(),
        );
    }

    /**
     * @return array<string, string|bool>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'active' => $this->active,
        ];
    }
}
