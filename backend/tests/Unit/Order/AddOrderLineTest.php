<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\AddOrderLine\AddOrderLine;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\Diners;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AddOrderLineTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_add_order_line_is_atomic_and_dispatches_audit_log(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $productUuid = Uuid::generate()->getValue();
        $userUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, $userUuid, '127.0.0.1');

        $order = Order::dddCreate(
            Uuid::create($orderUuid),
            $restaurantId,
            Uuid::generate(),
            Uuid::generate(),
            Diners::create(2),
        );

        $product = Product::dddCreate(
            Uuid::create($productUuid),
            ProductName::create('Croqueta'),
            ProductPrice::create(450),
            ProductStock::create(20),
            true,
            Uuid::generate(),
            Uuid::generate(),
            $restaurantId,
        );

        $tax = Tax::dddCreate(
            Uuid::generate(),
            TaxName::create('IVA 10%'),
            TaxPercentage::create(10),
            $restaurantId,
        );

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findById')->once()->with($orderUuid, $restaurantId)->andReturn($order);

        $productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $productRepository->shouldReceive('findById')->once()->with($productUuid, $restaurantId)->andReturn($product);

        $taxRepository = Mockery::mock(TaxRepositoryInterface::class);
        $taxRepository->shouldReceive('findById')->once()->with($product->taxId()->getValue(), $restaurantId)->andReturn($tax);

        $lineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $lineRepository->shouldReceive('save')->once()->with(Mockery::on(function (OrderLine $line) use ($orderUuid, $productUuid, $userUuid) {
            return $line->orderId()->getValue() === $orderUuid
                && $line->productId()->getValue() === $productUuid
                && $line->userId()->getValue() === $userUuid
                && $line->price() === 450
                && $line->taxPercentage() === 10
                && $line->quantity()->getValue() === 3;
        }));

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once()->with(Mockery::type(ActionLogged::class));

        $useCase = new AddOrderLine(
            $orderRepository,
            $lineRepository,
            $productRepository,
            $taxRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $response = $useCase($auditContext, $orderUuid, $productUuid, $userUuid, 3);

        $this->assertSame($productUuid, $response->productId);
        $this->assertSame($userUuid, $response->userId);
        $this->assertSame(3, $response->quantity);
        $this->assertSame(450, $response->price);
        $this->assertSame(10, $response->taxPercentage);
    }

    private function transactionManager(): TransactionManagerInterface
    {
        return new class implements TransactionManagerInterface {
            public function run(callable $callback): mixed
            {
                return $callback();
            }
        };
    }
}