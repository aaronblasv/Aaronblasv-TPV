<?php

declare(strict_types=1);

namespace Tests\Unit\Refund;

use App\Refund\Application\CreateRefund\CreateRefundResponse;
use App\Refund\Application\CreateRefund\CreateRefund;
use App\Refund\Domain\Exception\RefundExceedsAvailableQuantityException;
use App\Refund\Domain\Interfaces\RefundRepositoryInterface;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleReadRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleWriteRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateRefundTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_partial_refund_validates_quantity_after_locked_read(): void
    {
        $restaurantId = 1;
        $saleUuid = Uuid::generate()->getValue();
        $saleLineUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, Uuid::generate()->getValue(), '127.0.0.1');

        $sale = Sale::dddCreate(
            Uuid::create($saleUuid),
            $restaurantId,
            Uuid::generate(),
            Uuid::generate(),
            12,
            1000,
            100,
            0,
            0,
            1100,
        );

        $lockedLine = SaleLine::fromPersistence(
            $saleLineUuid,
            $restaurantId,
            $saleUuid,
            Uuid::generate()->getValue(),
            'Café',
            Uuid::generate()->getValue(),
            1,
            1000,
            10,
            1000,
            100,
            null,
            0,
            0,
            1100,
            1,
        );

        $saleReadRepository = Mockery::mock(SaleReadRepositoryInterface::class);
        $saleReadRepository->shouldReceive('findByUuid')->once()->with($restaurantId, $saleUuid)->andReturn($sale);
        $saleReadRepository->shouldReceive('findDomainLinesBySaleUuidForUpdate')
            ->once()
            ->with($restaurantId, $saleUuid, [$saleLineUuid])
            ->andReturn([$lockedLine]);

        $saleWriteRepository = Mockery::mock(SaleWriteRepositoryInterface::class);
        $saleWriteRepository->shouldNotReceive('update');
        $saleWriteRepository->shouldNotReceive('updateLine');

        $refundRepository = Mockery::mock(RefundRepositoryInterface::class);
        $refundRepository->shouldNotReceive('save');
        $refundRepository->shouldNotReceive('saveLine');
        $refundRepository->shouldNotReceive('saveLinesBatch');

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldNotReceive('dispatch');

        $useCase = new CreateRefund(
            $saleReadRepository,
            $saleWriteRepository,
            $refundRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $this->expectException(RefundExceedsAvailableQuantityException::class);

        $useCase(
            $auditContext,
            $saleUuid,
            'cash',
            null,
            false,
            [[
                'sale_line_uuid' => $saleLineUuid,
                'quantity' => 1,
            ]],
        );
    }

    public function test_partial_refund_returns_typed_response(): void
    {
        $restaurantId = 1;
        $saleUuid = Uuid::generate()->getValue();
        $saleLineUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, Uuid::generate()->getValue(), '127.0.0.1');

        $sale = Sale::dddCreate(
            Uuid::create($saleUuid),
            $restaurantId,
            Uuid::generate(),
            Uuid::generate(),
            12,
            1000,
            100,
            0,
            0,
            1100,
        );

        $line = SaleLine::fromPersistence(
            $saleLineUuid,
            $restaurantId,
            $saleUuid,
            Uuid::generate()->getValue(),
            'Café',
            Uuid::generate()->getValue(),
            2,
            1000,
            10,
            2000,
            200,
            null,
            0,
            0,
            2200,
            0,
        );

        $saleReadRepository = Mockery::mock(SaleReadRepositoryInterface::class);
        $saleReadRepository->shouldReceive('findByUuid')->once()->with($restaurantId, $saleUuid)->andReturn($sale);
        $saleReadRepository->shouldReceive('findDomainLinesBySaleUuidForUpdate')
            ->once()
            ->with($restaurantId, $saleUuid, [$saleLineUuid])
            ->andReturn([$line]);

        $saleWriteRepository = Mockery::mock(SaleWriteRepositoryInterface::class);
        $saleWriteRepository->shouldReceive('updateLine')->once();
        $saleWriteRepository->shouldReceive('update')->once();

        $refundRepository = Mockery::mock(RefundRepositoryInterface::class);
        $refundRepository->shouldReceive('save')->once();
        $refundRepository->shouldReceive('saveLinesBatch')->once()->with(Mockery::on(
            fn (array $lines): bool => count($lines) === 1
        ));

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once();

        $useCase = new CreateRefund(
            $saleReadRepository,
            $saleWriteRepository,
            $refundRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $response = $useCase(
            $auditContext,
            $saleUuid,
            'cash',
            null,
            false,
            [[
                'sale_line_uuid' => $saleLineUuid,
                'quantity' => 1,
            ]],
        );

        $this->assertInstanceOf(CreateRefundResponse::class, $response);
        $this->assertSame('partial', $response->refundType);
        $this->assertSame(1100, $response->totalAmount);
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