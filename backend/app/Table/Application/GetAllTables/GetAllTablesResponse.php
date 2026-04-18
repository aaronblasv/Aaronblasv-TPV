<?php

declare(strict_types=1);

namespace App\Table\Application\GetAllTables;

use App\Table\Domain\Entity\Table;

final readonly class GetAllTablesResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public string $zoneId,
        public ?string $mergedWith,
    ) {}

    public static function create(Table $table): self
    {
        return new self(
            $table->uuid()->getValue(),
            $table->name()->getValue(),
            $table->zoneId()->getValue(),
            $table->mergedWith()?->getValue(),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'zone_id' => $this->zoneId,
            'merged_with' => $this->mergedWith,
        ];
    }
}
