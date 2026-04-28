<?php

declare(strict_types=1);

namespace App\Sale\Domain\ReadModel;

final readonly class SaleServiceWindow
{
    /**
     * @param SaleServiceWindowLine[] $lines
     */
    public function __construct(
        public string $uuid,
        public int $windowNumber,
        public string $sentAt,
        public string $sentByUserName,
        public array $lines,
    ) {}
}
