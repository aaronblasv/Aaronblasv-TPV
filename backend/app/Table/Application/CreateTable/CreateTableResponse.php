<?php

declare(strict_types=1);

namespace App\Table\Application\CreateTable;

use App\Table\Domain\Entity\Table;

final readonly class CreateTableResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public string $zoneId,
    ) {}

    public static function create(Table $table): self
    {
        return new self(
            $table->uuid()->getValue(),
            $table->name()->getValue(),
            $table->zoneId()->getValue(),
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
            'zone_id' => $this->zoneId,
        ];
    }
}
