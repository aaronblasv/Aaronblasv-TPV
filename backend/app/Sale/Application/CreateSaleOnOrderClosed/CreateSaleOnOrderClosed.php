<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateSaleOnOrderClosed;

use App\Order\Domain\Event\OrderClosed;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleWriteRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class CreateSaleOnOrderClosed
{
    public function __construct(
        private SaleWriteRepositoryInterface $saleRepository,
    ) {}

    public function handle(OrderClosed $event): void
    {
        $saleUuid = Uuid::generate();

        $sale = Sale::dddCreate(
            $saleUuid,
            $event->restaurantId,
            $event->orderUuid,
            $event->closedByUserUuid,
            $event->ticketNumber,
            $event->subtotal,
            $event->taxAmount,
            $event->lineDiscountTotal,
            $event->orderDiscountTotal,
            $event->total,
        );
        $this->saleRepository->save($sale);

        foreach ($event->lines as $line) {
            $saleLine = SaleLine::dddCreate(
                Uuid::generate(),
                $event->restaurantId,
                $saleUuid,
                $line->uuid(),
                $line->userId(),
                $line->quantity()->getValue(),
                $line->price(),
                $line->taxPercentage(),
                $line->subtotal(),
                $line->taxAmount(),
                $line->discountType(),
                $line->discountValue(),
                $line->discountAmount(),
                $line->total(),
            );
            $this->saleRepository->saveLine($saleLine);
        }
    }
}
