<?php

declare(strict_types=1);

namespace App\Order\Application\AddOrderLine;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Exception\CannotAddLinesToClosedOrderException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Exception\ProductNotFoundInOrderContextException;
use App\Order\Domain\Exception\TaxNotFoundForProductException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\ValueObject\Quantity;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class AddOrderLine
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderLineRepositoryInterface $lineRepository,
        private ProductRepositoryInterface $productRepository,
        private TaxRepositoryInterface $taxRepository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(
        AuditContext $auditContext,
        string $orderUuid,
        string $productUuid,
        string $userUuid,
        int $quantity,
    ): AddOrderLineResponse {
        return $this->transactionManager->run(function () use ($auditContext, $orderUuid, $productUuid, $userUuid, $quantity) {
            $order = $this->orderRepository->findById($orderUuid, $auditContext->restaurantId);
            if (!$order) {
                throw new OrderNotFoundException($orderUuid);
            }
            if (!$order->status()->isOpen()) {
                throw new CannotAddLinesToClosedOrderException($orderUuid);
            }

            $product = $this->productRepository->findById($productUuid, $auditContext->restaurantId);
            if (!$product) {
                throw new ProductNotFoundInOrderContextException($productUuid, $orderUuid);
            }

            $tax = $this->taxRepository->findById($product->taxId()->getValue(), $auditContext->restaurantId);
            if (!$tax) {
                throw new TaxNotFoundForProductException($productUuid);
            }

            $line = OrderLine::dddCreate(
                Uuid::generate(),
                $auditContext->restaurantId,
                Uuid::create($orderUuid),
                Uuid::create($productUuid),
                Uuid::create($userUuid),
                Quantity::create($quantity),
                $product->price()->getValue(),
                $tax->percentage()->getValue(),
            );

            $this->lineRepository->save($line);

            $line->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'order.line.added',
                'order',
                $orderUuid,
                [
                    'line_uuid' => $line->uuid()->getValue(),
                    'product_id' => $productUuid,
                    'user_id' => $userUuid,
                    'quantity' => $quantity,
                ],
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$line->pullDomainEvents());

            return AddOrderLineResponse::create($line);
        });
    }
}