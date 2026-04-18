<?php

declare(strict_types=1);

namespace App\Payment\Application\RegisterPayment;

use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Interfaces\PaymentRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class RegisterPayment
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
    ) {}

    public function __invoke(
        string $orderUuid,
        string $userUuid,
        int $amount,
        string $method,
        ?string $description = null,
    ): RegisterPaymentResponse {
        $payment = Payment::dddCreate(
            Uuid::generate(),
            Uuid::create($orderUuid),
            Uuid::create($userUuid),
            $amount,
            $method,
            $description,
        );

        $this->repository->save($payment);

        $totalPaid = $this->repository->getTotalPaidByOrder($orderUuid);

        return RegisterPaymentResponse::create($payment, $totalPaid);
    }
}
