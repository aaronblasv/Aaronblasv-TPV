<?php

declare(strict_types=1);

namespace App\Refund\Application\CreateRefund;

use App\Refund\Domain\Entity\Refund;
use App\Refund\Domain\Entity\RefundLine;
use App\Refund\Domain\Exception\RefundExceedsAvailableQuantityException;
use App\Refund\Domain\Interfaces\RefundRepositoryInterface;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Domain\Interfaces\SaleReadRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleWriteRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class CreateRefund
{
    public function __construct(
        private SaleReadRepositoryInterface $saleReadRepository,
        private SaleWriteRepositoryInterface $saleWriteRepository,
        private RefundRepositoryInterface $refundRepository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(
        AuditContext $auditContext,
        string $saleUuid,
        string $method,
        ?string $reason,
        bool $refundAll,
        array $requestedLines,
    ): array {
        return $this->transactionManager->run(function () use ($auditContext, $saleUuid, $method, $reason, $refundAll, $requestedLines) {
            $sale = $this->saleReadRepository->findByUuid($auditContext->restaurantId, $saleUuid);
            if (!$sale) {
                throw new SaleNotFoundException($saleUuid);
            }

            $lines = $this->saleReadRepository->findDomainLinesBySaleUuid($auditContext->restaurantId, $saleUuid);
            $byUuid = [];
            foreach ($lines as $line) {
                $byUuid[$line->uuid()->getValue()] = $line;
            }

            $refundItems = [];

            if ($refundAll) {
                foreach ($lines as $line) {
                    $availableQuantity = $line->availableQuantity();
                    if ($availableQuantity <= 0) {
                        continue;
                    }

                    $refundItems[] = [$line, $availableQuantity];
                }
            } else {
                foreach ($requestedLines as $requestedLine) {
                    $lineUuid = (string) ($requestedLine['sale_line_uuid'] ?? '');
                    $quantity = (int) ($requestedLine['quantity'] ?? 0);
                    $line = $byUuid[$lineUuid] ?? null;

                    if (!$line || $quantity <= 0) {
                        continue;
                    }

                    if ($quantity > $line->availableQuantity()) {
                        throw new RefundExceedsAvailableQuantityException($lineUuid);
                    }

                    $refundItems[] = [$line, $quantity];
                }
            }

            $subtotal = 0;
            $taxAmount = 0;
            $total = 0;
            $refundLinePayload = [];

            foreach ($refundItems as [$line, $quantity]) {
                $lineSubtotal = (int) round($line->lineSubtotal() * ($quantity / $line->quantity()));
                $lineTax = (int) round($line->taxAmount() * ($quantity / $line->quantity()));
                $lineTotal = (int) round($line->lineTotal() * ($quantity / $line->quantity()));

                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;
                $total += $lineTotal;

                $refundLinePayload[] = [$line, $quantity, $lineSubtotal, $lineTax, $lineTotal];
            }

            $refund = Refund::dddCreate(
                Uuid::generate(),
                $auditContext->restaurantId,
                $sale->uuid(),
                Uuid::create($auditContext->userId),
                $refundAll ? 'full' : 'partial',
                $method,
                $reason,
                $subtotal,
                $taxAmount,
                $total,
            );

            $this->refundRepository->save($refund);

            foreach ($refundLinePayload as [$line, $quantity, $lineSubtotal, $lineTax, $lineTotal]) {
                $line->registerRefund($quantity);
                $this->saleWriteRepository->updateLine($line);

                $refundLine = RefundLine::dddCreate(
                    Uuid::generate(),
                    $refund->uuid(),
                    $line->uuid(),
                    $quantity,
                    $lineSubtotal,
                    $lineTax,
                    $lineTotal,
                );

                $this->refundRepository->saveLine($refundLine);
            }

            $sale->registerRefund($total);
            $this->saleWriteRepository->update($sale);

            $response = [
                'uuid' => $refund->uuid()->getValue(),
                'type' => $refund->type(),
                'method' => $refund->method(),
                'subtotal' => $refund->subtotal(),
                'tax_amount' => $refund->taxAmount(),
                'total' => $refund->total(),
            ];

            $refund->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'sale.refunded',
                'sale',
                $saleUuid,
                $response,
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$refund->pullDomainEvents());

            return $response;
        });
    }
}