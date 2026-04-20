<?php

declare(strict_types=1);

namespace App\CashShift\Application\CloseCashShift;

use App\CashShift\Domain\Exception\CashShiftNotFoundException;
use App\CashShift\Domain\Interfaces\CashShiftRepositoryInterface;
use App\CashShift\Domain\Interfaces\CashShiftSalesReadModelInterface;
use App\CashShift\Domain\ValueObject\ClosingCashSnapshot;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class CloseCashShift
{
    public function __construct(
        private CashShiftRepositoryInterface $repository,
        private CashShiftSalesReadModelInterface $salesReadModel,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $cashShiftUuid, int $countedCash, ?string $notes): CloseCashShiftResponse
    {
        return $this->transactionManager->run(function () use ($auditContext, $cashShiftUuid, $countedCash, $notes) {
            $cashShift = $this->repository->findByUuid($auditContext->restaurantId, $cashShiftUuid);
            if (!$cashShift || !$cashShift->status()->isOpen()) {
                throw new CashShiftNotFoundException($cashShiftUuid);
            }

            $summary = $this->salesReadModel->getWindowSummary($auditContext->restaurantId, $cashShift->openedAt(), null);

            $snapshot = ClosingCashSnapshot::create(
                Uuid::create($auditContext->userId),
                $summary->cashTotal->getValue(),
                $summary->cardTotal->getValue(),
                $summary->bizumTotal->getValue(),
                $summary->refundTotal->getValue(),
                $countedCash,
                $notes,
            );

            $cashShift->close($snapshot);

            $this->repository->update($cashShift);

            $response = CloseCashShiftResponse::create($cashShift);

            $cashShift->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'cash_shift.closed',
                'cash_shift',
                $cashShiftUuid,
                $response->toArray(),
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$cashShift->pullDomainEvents());

            return $response;
        });
    }
}