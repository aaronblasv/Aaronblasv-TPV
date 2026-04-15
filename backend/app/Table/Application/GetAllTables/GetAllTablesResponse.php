<?php

namespace App\Table\Application\GetAllTables;

use App\Table\Domain\Entity\Table;

final readonly class GetAllTablesResponse
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
