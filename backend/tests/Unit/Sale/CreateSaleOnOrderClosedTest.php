<?php

declare(strict_types=1);

namespace Tests\Unit\Sale;

use App\Order\Domain\Event\OrderClosed;
use App\Product\Domain\Entity\Product;
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
            ticketNumber: 15,
            subtotal: 2000,
            taxAmount: 200,
            lineDiscountTotal: 0,
            orderDiscountTotal: 0,
            total: 2200,
            lines: [$lineA, $lineB],
        );

        $repository = Mockery::mock(SaleWriteRepositoryInterface::class);
        $repository->shouldReceive('save')->once()->with(Mockery::type(Sale::class));
        $repository->shouldNotReceive('saveLine');
        $repository->shouldReceive('saveLinesBatch')->once()->with(Mockery::on(function (array $lines): bool {
            if (count($lines) !== 2) {
                return false;
            }

            foreach ($lines as $line) {
                if (!$line instanceof SaleLine) {
                    return false;
                }

                if ($line->productName() !== 'Café') {
                    return false;
                }
            }

            return true;
        }));

        $productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $productRepository->shouldReceive('findById')->twice()->andReturn(
            Product::fromPersistence(Uuid::generate()->getValue(), 'Café', 1000, 10, true, Uuid::generate()->getValue(), Uuid::generate()->getValue(), 1, null),
            Product::fromPersistence(Uuid::generate()->getValue(), 'Café', 1000, 10, true, Uuid::generate()->getValue(), Uuid::generate()->getValue(), 1, null),
        );

        $cacheRepository = Mockery::mock(CacheRepositoryInterface::class);
        $cacheRepository->shouldReceive('forgetByPrefix')->once()->with('dashboard:1:');

        $listener = new CreateSaleOnOrderClosed($repository, $productRepository, $cacheRepository);

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