<?php

namespace App\Payment\Application\RegisterPayment;

use App\Payment\Domain\Entity\Payment;

final readonly class RegisterPaymentResponse
{
    private function __construct(
        public string $uuid,
        public string $orderUuid,
        public int $amount,
        public string $method,
        public int $totalPaid,
        public ?string $description = null,
    ) {}

    public static function create(Payment $payment, int $totalPaid): self
    {
        return new self(
            $payment->uuid()->getValue(),
            $payment->orderId()->getValue(),
            $payment->amount(),
            $payment->method(),
            $totalPaid,
            $payment->description(),
        );
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'order_uuid' => $this->orderUuid,
            'amount' => $this->amount,
            'method' => $this->method,
            'total_paid' => $this->totalPaid,
            'description' => $this->description,
        ];
    }
}
