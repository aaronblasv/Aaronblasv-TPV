<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateSaleOnOrderClosed;

use App\Order\Domain\Event\OrderClosed;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleWriteRepositoryInterface;
use App\Shared\Domain\CacheRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class CreateSaleOnOrderClosed
{
    public function __construct(
        private SaleWriteRepositoryInterface $saleRepository,
        private ProductRepositoryInterface $productRepository,
        private CacheRepositoryInterface $cacheRepository,
    ) {}

    public function handle(OrderClosed $event): void
    {
        $saleUuid = Uuid::generate();
        $ticketNumber = $this->saleRepository->getNextTicketNumber($event->restaurantId);
        $productsById = $this->productRepository->findByIds(
            array_map(
                static fn ($line) => $line->productId()->getValue(),
                $event->lines,
            ),
            $event->restaurantId,
        );

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

        $saleLines = [];

        foreach ($event->lines as $line) {
            $product = $productsById[$line->productId()->getValue()] ?? null;

            if ($product === null) {
                throw new \RuntimeException(sprintf('Product %s not found while creating sale.', $line->productId()->getValue()));
            }

            $saleLines[] = SaleLine::dddCreate(
                Uuid::generate(),
                $event->restaurantId,
                $saleUuid,
                $line->uuid(),
                $product->name()->getValue(),
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
        }

        $this->saleRepository->saveLinesBatch($saleLines);
        $this->cacheRepository->forgetByPrefix("dashboard:{$event->restaurantId}:");
    }
}
