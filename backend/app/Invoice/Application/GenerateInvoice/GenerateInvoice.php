<?php

declare(strict_types=1);

namespace App\Invoice\Application\GenerateInvoice;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Interfaces\InvoiceOrderDataProviderInterface;
use App\Invoice\Domain\Interfaces\InvoiceRepositoryInterface;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\TransactionManagerInterface;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

class GenerateInvoice
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceOrderDataProviderInterface $orderDataProvider,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $orderUuid): GenerateInvoiceResponse
    {
        return $this->transactionManager->run(function () use ($auditContext, $orderUuid) {
            $orderData = $this->orderDataProvider->getOrderForInvoice($orderUuid, $auditContext->restaurantId);

            if (!$orderData) {
                throw new OrderNotFoundException($orderUuid);
            }

            $invoiceNumber = $this->invoiceRepository->getNextInvoiceNumber();

            $invoice = Invoice::dddCreate(
                Uuid::generate(),
                Uuid::create($orderData->orderUuid),
                $invoiceNumber,
                $orderData->subtotal,
                $orderData->taxAmount,
                $orderData->total,
            );

            $this->invoiceRepository->save($invoice);

            $response = GenerateInvoiceResponse::create($invoice);

            $invoice->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'invoice.generated',
                'invoice',
                $response->uuid,
                [
                    'order_uuid' => $orderUuid,
                    'invoice_number' => $response->invoiceNumber,
                    'total' => $response->total,
                ],
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$invoice->pullDomainEvents());

            return $response;
        });
    }
}
