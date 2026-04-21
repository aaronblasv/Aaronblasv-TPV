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
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class AddOrderLine
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderLineRepositoryInterface $lineRepository,
        private ProductRepositoryInterface $productRepository,
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(
        int $restaurantId,
        string $orderUuid,
        string $productUuid,
        string $userUuid,
        int $quantity,
    ): AddOrderLineResponse {
        $order = $this->orderRepository->findById($orderUuid, $restaurantId);
        if (!$order) {
            throw new OrderNotFoundException($orderUuid);
        }
        if (!$order->status()->isOpen()) {
            throw new CannotAddLinesToClosedOrderException($orderUuid);
        }

        $product = $this->productRepository->findById($productUuid, $restaurantId);
        if (!$product) {
            throw new ProductNotFoundInOrderContextException($productUuid, $orderUuid);
        }

        $tax = $this->taxRepository->findById($product->taxId()->getValue(), $restaurantId);
        if (!$tax) {
            throw new TaxNotFoundForProductException($productUuid);
        }

        $line = OrderLine::dddCreate(
            Uuid::generate(),
            $restaurantId,
            Uuid::create($orderUuid),
            Uuid::create($productUuid),
            Uuid::create($userUuid),
            Quantity::create($quantity),
            $product->price()->getValue(),
            $tax->percentage()->getValue(),
        );

        $this->lineRepository->save($line);

        return AddOrderLineResponse::create($line);
    }
}