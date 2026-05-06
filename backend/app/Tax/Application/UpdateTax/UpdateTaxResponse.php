<?php

declare(strict_types=1);

namespace App\Tax\Application\UpdateTax;

use App\Tax\Domain\Entity\Tax;

final readonly class UpdateTaxResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public float $percentage,
    ) {}

    public static function create(Tax $tax): self
    {
        return new self(
            $tax->uuid()->getValue(),
            $tax->name()->getValue(),
            $tax->percentage()->asPercentage(),
        );
    }

    /**
     * @return array<string, string|float>
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
