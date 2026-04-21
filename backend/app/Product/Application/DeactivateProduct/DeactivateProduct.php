<?php

declare(strict_types=1);

namespace App\Product\Application\DeactivateProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;

class DeactivateProduct
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $productUuid): void
    {
        $this->transactionManager->run(function () use ($auditContext, $productUuid) {
            $product = $this->repository->findById($productUuid, $auditContext->restaurantId);

            if ($product === null) {
                throw new ProductNotFoundException($productUuid);
            }

            $product->deactivate();

            $this->repository->save($product);

            $this->domainEventBus->dispatch(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'product.deactivated',
                'product',
                $productUuid,
                null,
                $auditContext->ipAddress,
            ));
        });
    }
}
