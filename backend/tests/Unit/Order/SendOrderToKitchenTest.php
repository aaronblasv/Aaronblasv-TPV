<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\SendOrderToKitchen\SendOrderToKitchen;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\ServiceWindowRepositoryInterface;
use App\Order\Domain\ValueObject\Diners;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SendOrderToKitchenTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_marks_pending_lines_with_single_bulk_update(): void
    {
        $restaurantId = 1;
        $orderUuid = Uuid::generate()->getValue();
        $auditContext = new AuditContext($restaurantId, Uuid::generate()->getValue(), '127.0.0.1');

        $order = Order::dddCreate(
            Uuid::create($orderUuid),
            $restaurantId,
            Uuid::generate(),
            Uuid::generate(),
            Diners::create(2),
        );

        $firstLineUuid = Uuid::generate()->getValue();
        $secondLineUuid = Uuid::generate()->getValue();
        $firstProductUuid = Uuid::generate()->getValue();
        $secondProductUuid = Uuid::generate()->getValue();

        $pendingLineA = Mockery::mock();
        $pendingLineA->shouldReceive('isSentToKitchen')->andReturn(false);
        $pendingLineA->shouldReceive('uuid')->andReturn(Uuid::create($firstLineUuid));
        $pendingLineA->shouldReceive('productId')->andReturn(Uuid::create($firstProductUuid));
        $pendingLineA->shouldReceive('quantity->getValue')->andReturn(1);
        $pendingLineA->shouldReceive('price')->andReturn(1000);
        $pendingLineA->shouldReceive('taxPercentage')->andReturn(10);
        $pendingLineA->shouldReceive('discountType')->andReturn(null);
        $pendingLineA->shouldReceive('discountValue')->andReturn(0);
        $pendingLineA->shouldReceive('discountAmount')->andReturn(0);
        $pendingLineA->shouldReceive('subtotal')->andReturn(1000);
        $pendingLineA->shouldReceive('taxAmount')->andReturn(100);
        $pendingLineA->shouldReceive('total')->andReturn(1100);

        $pendingLineB = Mockery::mock();
        $pendingLineB->shouldReceive('isSentToKitchen')->andReturn(false);
        $pendingLineB->shouldReceive('uuid')->andReturn(Uuid::create($secondLineUuid));
        $pendingLineB->shouldReceive('productId')->andReturn(Uuid::create($secondProductUuid));
        $pendingLineB->shouldReceive('quantity->getValue')->andReturn(1);
        $pendingLineB->shouldReceive('price')->andReturn(1200);
        $pendingLineB->shouldReceive('taxPercentage')->andReturn(10);
        $pendingLineB->shouldReceive('discountType')->andReturn(null);
        $pendingLineB->shouldReceive('discountValue')->andReturn(0);
        $pendingLineB->shouldReceive('discountAmount')->andReturn(0);
        $pendingLineB->shouldReceive('subtotal')->andReturn(1200);
        $pendingLineB->shouldReceive('taxAmount')->andReturn(120);
        $pendingLineB->shouldReceive('total')->andReturn(1320);

        $alreadySentLine = Mockery::mock();
        $alreadySentLine->shouldReceive('isSentToKitchen')->andReturn(true);

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findById')->once()->with($orderUuid, $restaurantId)->andReturn($order);

        $lineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $lineRepository->shouldReceive('findAllByOrderId')->once()->with($orderUuid, $restaurantId)->andReturn([$pendingLineA, $pendingLineB, $alreadySentLine]);
        $lineRepository->shouldReceive('bulkMarkSentToKitchen')
            ->once()
            ->with([$firstLineUuid, $secondLineUuid], $restaurantId, Mockery::type(DomainDateTime::class));
        $lineRepository->shouldNotReceive('update');

        $serviceWindowRepository = Mockery::mock(ServiceWindowRepositoryInterface::class);
        $serviceWindowRepository->shouldReceive('nextWindowNumberForOrder')->once()->with($orderUuid, $restaurantId)->andReturn(2);
        $serviceWindowRepository->shouldReceive('save')->once();

        $productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $productRepository->shouldReceive('findById')->once()->with($firstProductUuid, $restaurantId)->andReturn(
            Product::fromPersistence($firstProductUuid, 'Café', 1000, 10, true, Uuid::generate()->getValue(), Uuid::generate()->getValue(), $restaurantId, null)
        );
        $productRepository->shouldReceive('findById')->once()->with($secondProductUuid, $restaurantId)->andReturn(
            Product::fromPersistence($secondProductUuid, 'Té', 1200, 10, true, Uuid::generate()->getValue(), Uuid::generate()->getValue(), $restaurantId, null)
        );

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')->once()->with($auditContext->userId, $restaurantId)->andReturn(
            User::fromPersistence(
                $auditContext->userId,
                'Camarero 1',
                'waiter@example.com',
                password_hash('secret', PASSWORD_BCRYPT),
                'waiter',
                $restaurantId,
                null,
                null,
                new \DateTimeImmutable(),
                new \DateTimeImmutable(),
            )
        );

        $domainEventBus = Mockery::mock(DomainEventBusInterface::class);
        $domainEventBus->shouldReceive('dispatch')->once()->withArgs(function (...$events) use ($orderUuid, $auditContext) {
            foreach ($events as $event) {
                if ($event instanceof ActionLogged) {
                    return $event->action === 'order.sent_to_kitchen'
                        && $event->entityUuid === $orderUuid
                        && $event->userId === $auditContext->userId
                        && $event->data === ['lines_sent' => 2, 'service_window_number' => 2];
                }
            }

            return false;
        });

        $useCase = new SendOrderToKitchen(
            $orderRepository,
            $lineRepository,
            $serviceWindowRepository,
            $productRepository,
            $userRepository,
            $this->transactionManager(),
            $domainEventBus,
        );

        $useCase($auditContext, $orderUuid);
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