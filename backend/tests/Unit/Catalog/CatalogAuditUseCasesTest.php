<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Family\Application\DeactivateFamily\DeactivateFamily;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use App\Product\Application\ActivateProduct\ActivateProduct;
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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CatalogAuditUseCasesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_activate_product_persists_state_and_dispatches_audit_log(): void
    {
        $restaurantId = 1;
        $productUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, Uuid::generate()->getValue(), '127.0.0.1');

        $product = Product::dddCreate(
            Uuid::create($productUuid),
            ProductName::create('Refresco'),
            ProductPrice::create(250),
            ProductStock::create(30),
            false,
            Uuid::generate(),
            Uuid::generate(),
            $restaurantId,
        );

        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($productUuid, $restaurantId)->andReturn($product);
        $repository->shouldReceive('save')->once()->with(Mockery::on(fn (Product $savedProduct) => $savedProduct->active() === true));

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once()->with(Mockery::type(ActionLogged::class));

        $useCase = new ActivateProduct($repository, $this->transactionManager(), $domainEventBus);
        $useCase($auditContext, $productUuid);
    }

    public function test_deactivate_family_persists_state_and_dispatches_audit_log(): void
    {
        $restaurantId = 1;
        $familyUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, Uuid::generate()->getValue(), '127.0.0.1');

        $family = Family::dddCreate(
            Uuid::create($familyUuid),
            FamilyName::create('Entrantes'),
            true,
            $restaurantId,
        );

        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($familyUuid, $restaurantId)->andReturn($family);
        $repository->shouldReceive('save')->once()->with(Mockery::on(fn (Family $savedFamily) => $savedFamily->active() === false));

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once()->with(Mockery::type(ActionLogged::class));

        $useCase = new DeactivateFamily($repository, $this->transactionManager(), $domainEventBus);
        $useCase($auditContext, $familyUuid);
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