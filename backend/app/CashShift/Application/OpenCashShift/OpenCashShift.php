<?php

declare(strict_types=1);

namespace App\CashShift\Application\OpenCashShift;

use App\CashShift\Domain\Entity\CashShift;
use App\CashShift\Domain\Exception\CashShiftAlreadyOpenException;
use App\CashShift\Domain\Interfaces\CashShiftRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class OpenCashShift
{
    public function __construct(
        private CashShiftRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, int $openingCash, ?string $notes): OpenCashShiftResponse
    {
        return $this->transactionManager->run(function () use ($auditContext, $openingCash, $notes) {
            if ($this->repository->findOpenByRestaurant($auditContext->restaurantId)) {
                throw new CashShiftAlreadyOpenException();
            }

            $cashShift = CashShift::open(
                Uuid::generate(),
                $auditContext->restaurantId,
                Uuid::create($auditContext->userId),
                $openingCash,
                $notes,
            );

            $this->repository->save($cashShift);

            $response = OpenCashShiftResponse::create($cashShift);

            $cashShift->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'cash_shift.opened',
                'cash_shift',
                $cashShift->uuid()->getValue(),
                $response->toArray(),
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$cashShift->pullDomainEvents());

            return $response;
        });
    }
}