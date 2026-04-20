<?php

declare(strict_types=1);

namespace App\CashShift\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class ClosingCashSnapshot
{
    private function __construct(
        public Uuid $closedByUserId,
        public int $cashTotal,
        public int $cardTotal,
        public int $bizumTotal,
        public int $refundTotal,
        public int $countedCash,
        public ?string $notes,
    ) {}

    public static function create(
        Uuid $closedByUserId,
        int $cashTotal,
        int $cardTotal,
        int $bizumTotal,
        int $refundTotal,
        int $countedCash,
        ?string $notes,
    ): self {
        return new self($closedByUserId, $cashTotal, $cardTotal, $bizumTotal, $refundTotal, $countedCash, $notes);
    }
}