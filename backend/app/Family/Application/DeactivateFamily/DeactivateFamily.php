<?php

declare(strict_types=1);

namespace App\Family\Application\DeactivateFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;

class DeactivateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $familyUuid): void
    {
        $this->transactionManager->run(function () use ($auditContext, $familyUuid) {
            $family = $this->repository->findById($familyUuid, $auditContext->restaurantId);

            if ($family === null) {
                throw new FamilyNotFoundException($familyUuid);
            }

            $family->deactivate();

            $this->repository->save($family);

            $this->domainEventBus->dispatch(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'family.deactivated',
                'family',
                $familyUuid,
                null,
                $auditContext->ipAddress,
            ));
        });
    }
}
