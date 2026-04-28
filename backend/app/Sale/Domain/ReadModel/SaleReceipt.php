<?php

declare(strict_types=1);

namespace App\Sale\Domain\ReadModel;

final readonly class SaleReceipt
{
    /**
     * @param SaleLineDetail[] $lines
     * @param SaleServiceWindow[] $serviceWindows
     */
    public function __construct(
        public string $restaurantName,
        public string $restaurantLegalName,
        public string $restaurantTaxId,
        public int $ticketNumber,
        public string $valueDate,
        public string $tableName,
        public string $openedAt,
        public ?string $closedAt,
        public string $openUserName,
        public string $closeUserName,
        public int $subtotal,
        public int $taxAmount,
        public int $lineDiscountTotal,
        public int $orderDiscountTotal,
        public int $total,
        public int $refundedTotal,
        public int $netTotal,
        public array $lines,
        public array $serviceWindows,
    ) {}
}
