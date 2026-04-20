<?php

declare(strict_types=1);

namespace App\CashShift\Application\CloseCashShift;

use App\CashShift\Domain\Entity\CashShift;

final readonly class CloseCashShiftResponse
{
    private function __construct(
        public string $uuid,
        public string $status,
        public int $openingCash,
        public int $cashTotal,
        public int $cardTotal,
        public int $bizumTotal,
        public int $refundTotal,
        public int $countedCash,
        public int $cashDifference,
        public string $openedAt,
        public string $closedAt,
    ) {}

    public static function create(CashShift $cashShift): self
    {
        $countedCash = $cashShift->countedCash();
        $closedAt = $cashShift->closedAt();

        if ($countedCash === null || $closedAt === null) {
            throw new \LogicException('Closed cash shift must have counted cash and closed date.');
        }

        return new self(
            uuid: $cashShift->uuid()->getValue(),
            status: $cashShift->status()->value,
            openingCash: $cashShift->openingCash()->getValue(),
            cashTotal: $cashShift->cashTotal()->getValue(),
            cardTotal: $cashShift->cardTotal()->getValue(),
            bizumTotal: $cashShift->bizumTotal()->getValue(),
            refundTotal: $cashShift->refundTotal()->getValue(),
            countedCash: $countedCash->getValue(),
            cashDifference: $cashShift->cashDifference()->getValue(),
            openedAt: $cashShift->openedAt()->format('Y-m-d H:i:s'),
            closedAt: $closedAt->format('Y-m-d H:i:s'),
        );
    }

    /** @return array<string,int|string> */
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
            'counted_cash' => $this->countedCash,
            'cash_difference' => $this->cashDifference,
            'opened_at' => $this->openedAt,
            'closed_at' => $this->closedAt,
        ];
    }
}