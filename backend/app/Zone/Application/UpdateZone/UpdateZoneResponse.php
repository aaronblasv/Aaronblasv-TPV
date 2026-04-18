<?php

declare(strict_types=1);

namespace App\Zone\Application\UpdateZone;

use App\Zone\Domain\Entity\Zone;

final readonly class UpdateZoneResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
    ) {}

    public static function create(Zone $zone): self
    {
        return new self(
            $zone->uuid()->getValue(),
            $zone->name()->getValue(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
        ];
    }
}
