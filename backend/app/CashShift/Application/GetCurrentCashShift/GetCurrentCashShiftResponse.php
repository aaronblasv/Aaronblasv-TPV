<?php

declare(strict_types=1);

namespace App\CashShift\Application\GetCurrentCashShift;

use App\CashShift\Domain\Entity\CashShift;
use App\CashShift\Domain\ReadModel\CashShiftSummary;

final readonly class GetCurrentCashShiftResponse
{
    private function __construct(
        public string $uuid,
        public string $status,
        public int $openingCash,
        public int $cashTotal,
        public int $cardTotal,
        public int $bizumTotal,
        public int $refundTotal,
        public int $expectedCash,
        public ?string $notes,
        public string $openedAt,
    ) {}

    public static function create(CashShift $cashShift, CashShiftSummary $summary): self
    {
        return new self(
            uuid: $cashShift->uuid()->getValue(),
            status: $cashShift->status()->value,
            openingCash: $cashShift->openingCash()->getValue(),
            cashTotal: $summary->cashTotal->getValue(),
            cardTotal: $summary->cardTotal->getValue(),
            bizumTotal: $summary->bizumTotal->getValue(),
            refundTotal: $summary->refundTotal->getValue(),
            expectedCash: $summary->expectedCash($cashShift->openingCash())->getValue(),
            notes: $cashShift->notes(),
            openedAt: $cashShift->openedAt()->format('Y-m-d H:i:s'),
        );
    }

    /** @return array<string,int|string|null> */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'opening_cash' => $this->openingCash,
            'cash_total' => $this->cashTotal,
            'card_total' => $this->cardTotal,
            'bizum_total' => $this->bizumTotal,
            'refund_total' => $this->refundTotal,
            'expected_cash' => $this->expectedCash,
            'notes' => $this->notes,
            'opened_at' => $this->openedAt,
        ];
    }
}