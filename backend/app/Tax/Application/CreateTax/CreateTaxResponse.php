<?php

declare(strict_types=1);

namespace App\Tax\Application\CreateTax;

use App\Tax\Domain\Entity\Tax;

final readonly class CreateTaxResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public int $percentage,
    ) {}

    public static function create(Tax $tax): self
    {
        return new self(
            $tax->uuid()->getValue(),
            $tax->name()->getValue(),
            $tax->percentage()->getValue(),
        );
    }

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'percentage' => $this->percentage,
        ];
    }
}
