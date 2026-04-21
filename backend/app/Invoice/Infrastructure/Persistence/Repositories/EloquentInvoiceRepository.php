<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Repositories;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Interfaces\InvoiceRepositoryInterface;
use App\Invoice\Infrastructure\Persistence\Models\EloquentInvoice;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(
        private EloquentInvoice $model,
        private EloquentOrder $orderModel,
    ) {}

    public function save(Invoice $invoice): void
    {
        $order = $this->orderModel->newQuery()->where('uuid', $invoice->orderId()->getValue())->first();

        if (!$order) {
            throw new OrderNotFoundException($invoice->orderId()->getValue());
        }

        $this->model->newQuery()->create([
            'uuid'           => $invoice->uuid()->getValue(),
            'order_id'       => $order->id,
            'invoice_number' => $invoice->invoiceNumber(),
            'subtotal'       => $invoice->subtotal(),
            'tax_amount'     => $invoice->taxAmount(),
            'total'          => $invoice->total(),
            'issued_at'      => $invoice->issuedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByOrderId(string $orderUuid): ?Invoice
    {
        $order = $this->orderModel->newQuery()->where('uuid', $orderUuid)->first();

        if (!$order) {
            return null;
        }

        $eloquentInvoice = $this->model->newQuery()->where('order_id', $order->id)->first();

        return $eloquentInvoice ? $this->toDomain($eloquentInvoice) : null;
    }

    public function getNextInvoiceNumber(): string
    {
        $count = $this->model->newQuery()->lockForUpdate()->count() + 1;
        return 'INV-' . date('Ymd') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function toDomain(EloquentInvoice $invoice): Invoice
    {
        $orderUuid = $this->orderModel->newQuery()->find($invoice->order_id)->uuid;

        return Invoice::fromPersistence(
            $invoice->uuid,
            $orderUuid,
            $invoice->invoice_number,
            $invoice->subtotal,
            $invoice->tax_amount,
            $invoice->total,
            new \DateTimeImmutable($invoice->issued_at),
        );
    }
}
