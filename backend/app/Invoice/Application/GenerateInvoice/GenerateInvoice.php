<?php

namespace App\Invoice\Application\GenerateInvoice;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Interfaces\InvoiceRepositoryInterface;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class GenerateInvoice
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private OrderRepositoryInterface $orderRepository,
        private OrderLineRepositoryInterface $lineRepository,
    ) {}

    public function __invoke(string $orderUuid, int $restaurantId): GenerateInvoiceResponse
    {
        $order = $this->orderRepository->findById($orderUuid, $restaurantId);

        if (!$order) {
            throw new OrderNotFoundException($orderUuid);
        }

        $lines = $this->lineRepository->findAllByOrderId($orderUuid, $restaurantId);

        $subtotal = $order->calculateSubtotal($lines);
        $taxAmount = $order->calculateTaxAmount($lines);
        $total = $subtotal + $taxAmount;

        $invoiceNumber = $this->invoiceRepository->getNextInvoiceNumber();

        $invoice = Invoice::dddCreate(
            Uuid::generate(),
            $order->uuid(),
            $invoiceNumber,
            $subtotal,
            $taxAmount,
            $total,
        );

        $this->invoiceRepository->save($invoice);

        return GenerateInvoiceResponse::create($invoice);
    }
}
