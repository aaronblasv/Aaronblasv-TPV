<?php

declare(strict_types=1);

namespace App\Order\Application\OpenOrder;

use App\CashShift\Domain\Interfaces\CashShiftRepositoryInterface;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\CannotOpenOrderOnMergedTableException;
use App\Order\Domain\Exception\CashShiftNotOpenException;
use App\Order\Domain\Exception\TableAlreadyHasOpenOrderException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\Diners;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Domain\Exception\TableNotFoundException;
use App\Table\Domain\Interfaces\TableRepositoryInterface;

class OpenOrder
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private TableRepositoryInterface $tableRepository,
        private CashShiftRepositoryInterface $cashShiftRepository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(
        AuditContext $auditContext,
        string $tableUuid,
        string $openedByUserUuid,
        int $diners,
    ): OpenOrderResponse {
        return $this->transactionManager->run(function () use ($auditContext, $tableUuid, $openedByUserUuid, $diners) {
            if (! $this->cashShiftRepository->findOpenByRestaurant($auditContext->restaurantId)) {
                throw new CashShiftNotOpenException;
            }

            $table = $this->tableRepository->findById($tableUuid, $auditContext->restaurantId);
            if ($table === null) {
                throw new TableNotFoundException($tableUuid);
            }

            if ($table->isMerged()) {
                throw new CannotOpenOrderOnMergedTableException;
            }

            $existing = $this->repository->findOpenByTableId($tableUuid, $auditContext->restaurantId);
            if ($existing) {
                throw new TableAlreadyHasOpenOrderException($tableUuid);
            }

            $order = Order::dddCreate(
                Uuid::generate(),
                $auditContext->restaurantId,
                Uuid::create($tableUuid),
                Uuid::create($openedByUserUuid),
                Diners::create($diners),
            );

            $this->repository->save($order);

            $order->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $openedByUserUuid,
                'order.opened',
                'order',
                $order->uuid()->getValue(),
                [
                    'table_id' => $tableUuid,
                    'diners' => $diners,
                    'opened_by_user_id' => $openedByUserUuid,
                ],
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$order->pullDomainEvents());

            return OpenOrderResponse::create($order);
        });
    }
}
