<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateSaleOnOrderClosed;

use App\Order\Domain\Event\OrderClosed;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class CreateSaleOnOrderClosed
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
    ) {}

    public function handle(OrderClosed $event): void
    {
        $ticketNumber = $this->saleRepository->getNextTicketNumber($event->restaurantId);
        $saleUuid = Uuid::generate();

        $sale = Sale::dddCreate(
            $saleUuid,
            $event->restaurantId,
            $event->orderUuid,
            $event->closedByUserUuid,
            $ticketNumber,
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
