<?php

declare(strict_types=1);

namespace Tests\Unit\Sale;

use App\Order\Domain\Event\OrderClosed;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Sale\Application\CreateSaleOnOrderClosed\CreateSaleOnOrderClosed;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleWriteRepositoryInterface;
use App\Shared\Domain\CacheRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateSaleOnOrderClosedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_handle_persists_sale_lines_in_batch(): void
    {
        $lineA = $this->orderLineMock();
        $lineB = $this->orderLineMock();

        $event = new OrderClosed(
            orderUuid: Uuid::generate(),
            restaurantId: 1,
            closedByUserUuid: Uuid::generate(),
            subtotal: 2000,
            taxAmount: 200,
            lineDiscountTotal: 0,
            orderDiscountTotal: 0,
            total: 2200,
            lines: [$lineA, $lineB],
        );

        $repository = Mockery::mock(SaleWriteRepositoryInterface::class);
        $repository->shouldReceive('getNextTicketNumber')->once()->with(1)->andReturn(15);
        $repository->shouldReceive('save')->once()->with(Mockery::type(Sale::class));
        $repository->shouldNotReceive('saveLine');
        $repository->shouldReceive('saveLinesBatch')->once()->with(Mockery::on(function (array $lines): bool {
            if (count($lines) !== 2) {
                return false;
            }

            foreach ($lines as $line) {
                if (! $line instanceof SaleLine) {
                    return false;
                }

                if ($line->productName() !== 'Café') {
                    return false;
                }
            }

            return true;
        }));

        $productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $productRepository->shouldReceive('findByIds')->once()->with(Mockery::type('array'), 1)->andReturn([
            $lineA->productId()->getValue() => Product::fromPersistence($lineA->productId()->getValue(), 'Café', 1000, 10, true, Uuid::generate()->getValue(), Uuid::generate()->getValue(), 1, null),
            $lineB->productId()->getValue() => Product::fromPersistence($lineB->productId()->getValue(), 'Café', 1000, 10, true, Uuid::generate()->getValue(), Uuid::generate()->getValue(), 1, null),
        ]);

        $cacheRepository = Mockery::mock(CacheRepositoryInterface::class);
        $cacheRepository->shouldReceive('forgetByPrefix')->once()->with('dashboard:1:');

        $listener = new CreateSaleOnOrderClosed($repository, $productRepository, $cacheRepository);

        $listener->handle($event);
    }

    public function test_handle_throws_domain_exception_when_product_is_missing(): void
    {
        $line = $this->orderLineMock();

        $event = new OrderClosed(
            orderUuid: Uuid::generate(),
            restaurantId: 1,
            closedByUserUuid: Uuid::generate(),
            subtotal: 1000,
            taxAmount: 100,
            lineDiscountTotal: 0,
            orderDiscountTotal: 0,
            total: 1100,
            lines: [$line],
        );

        $repository = Mockery::mock(SaleWriteRepositoryInterface::class);
        $repository->shouldReceive('getNextTicketNumber')->once()->with(1)->andReturn(15);
        $repository->shouldReceive('save')->once()->with(Mockery::type(Sale::class));
        $repository->shouldNotReceive('saveLine');
        $repository->shouldNotReceive('saveLinesBatch');

        $productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $productRepository->shouldReceive('findByIds')->once()->with(Mockery::type('array'), 1)->andReturn([]);

        $cacheRepository = Mockery::mock(CacheRepositoryInterface::class);
        $cacheRepository->shouldNotReceive('forgetByPrefix');

        $listener = new CreateSaleOnOrderClosed($repository, $productRepository, $cacheRepository);

        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage("Product '{$line->productId()->getValue()}' not found.");

        $listener->handle($event);
    }

    private function orderLineMock(): object
    {
        $productUuid = Uuid::generate();

        $line = Mockery::mock();
        $line->shouldReceive('uuid')->andReturn(Uuid::generate());
        $line->shouldReceive('productId')->andReturn($productUuid);
        $line->shouldReceive('userId')->andReturn(Uuid::generate());
        $line->shouldReceive('quantity')->andReturn(new class
        {
            public function getValue(): int
            {
                return 1;
            }
        });
        $line->shouldReceive('price')->andReturn(1000);
        $line->shouldReceive('taxPercentage')->andReturn(10);
        $line->shouldReceive('subtotal')->andReturn(1000);
        $line->shouldReceive('taxAmount')->andReturn(100);
        $line->shouldReceive('discountType')->andReturn(null);
        $line->shouldReceive('discountValue')->andReturn(0);
        $line->shouldReceive('discountAmount')->andReturn(0);
        $line->shouldReceive('total')->andReturn(1100);

        return $line;
    }
}
