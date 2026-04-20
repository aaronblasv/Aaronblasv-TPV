<?php

declare(strict_types=1);

namespace App\CashShift\Application\OpenCashShift;

use App\CashShift\Domain\Entity\CashShift;

final readonly class OpenCashShiftResponse
{
    private function __construct(
        public string $uuid,
        public string $status,
        public int $openingCash,
        public string $openedAt,
    ) {}

    public static function create(CashShift $cashShift): self
    {
        return new self(
            uuid: $cashShift->uuid()->getValue(),
            status: $cashShift->status()->value,
            openingCash: $cashShift->openingCash()->getValue(),
            openedAt: $cashShift->openedAt()->format('Y-m-d H:i:s'),
        );
    }

    /** @return array{uuid:string,status:string,opening_cash:int,opened_at:string} */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'opening_cash' => $this->openingCash,
            'opened_at' => $this->openedAt,
        ];
    }
}