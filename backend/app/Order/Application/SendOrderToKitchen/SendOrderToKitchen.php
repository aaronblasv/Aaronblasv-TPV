<?php

declare(strict_types=1);

namespace App\Order\Application\SendOrderToKitchen;

use App\Order\Domain\Entity\ServiceWindow;
use App\Order\Domain\Entity\ServiceWindowLine;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\ServiceWindowRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Context\AuditContext;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class SendOrderToKitchen
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderLineRepositoryInterface $orderLineRepository,
        private ServiceWindowRepositoryInterface $serviceWindowRepository,
        private ProductRepositoryInterface $productRepository,
        private UserRepositoryInterface $userRepository,
        private TransactionManagerInterface $transactionManager,
        private DomainEventBusInterface $domainEventBus,
    ) {}

    public function __invoke(AuditContext $auditContext, string $orderUuid): void
    {
        $this->transactionManager->run(function () use ($auditContext, $orderUuid) {
            $order = $this->orderRepository->findById($orderUuid, $auditContext->restaurantId);
            if (!$order) {
                throw new OrderNotFoundException($orderUuid);
            }

            $lines = $this->orderLineRepository->findAllByOrderId($orderUuid, $auditContext->restaurantId);
            $pendingLines = array_filter($lines, static fn($line) => !$line->isSentToKitchen());

            if (empty($pendingLines)) {
                return;
            }

            $sentAt = DomainDateTime::now();
            $sentByUser = $this->userRepository->findById($auditContext->userId, $auditContext->restaurantId);

            if ($sentByUser === null) {
                throw new \RuntimeException(sprintf('User %s not found for service window.', $auditContext->userId));
            }

            $windowNumber = $this->serviceWindowRepository->nextWindowNumberForOrder($orderUuid, $auditContext->restaurantId);
            $serviceWindowLines = [];

            foreach ($pendingLines as $line) {
                $product = $this->productRepository->findById($line->productId()->getValue(), $auditContext->restaurantId);

                if ($product === null) {
                    throw new \RuntimeException(sprintf('Product %s not found for service window.', $line->productId()->getValue()));
                }

                $serviceWindowLines[] = ServiceWindowLine::dddCreate(
                    Uuid::generate(),
                    $auditContext->restaurantId,
                    $line->uuid(),
                    $product->name()->getValue(),
                    $line->quantity()->getValue(),
                    $line->price(),
                    $line->taxPercentage(),
                    $line->discountType(),
                    $line->discountValue(),
                    $line->discountAmount(),
                    $line->subtotal(),
                    $line->taxAmount(),
                    $line->total(),
                );
            }

            $this->serviceWindowRepository->save(ServiceWindow::dddCreate(
                Uuid::generate(),
                $auditContext->restaurantId,
                Uuid::create($orderUuid),
                Uuid::create($auditContext->userId),
                $sentByUser->name()->getValue(),
                $windowNumber,
                $sentAt,
                $serviceWindowLines,
            ));

            $this->orderLineRepository->bulkMarkSentToKitchen(
                array_map(static fn ($line) => $line->uuid()->getValue(), $pendingLines),
                $auditContext->restaurantId,
                $sentAt,
            );

            $order->recordDomainEvent(ActionLogged::create(
                $auditContext->restaurantId,
                $auditContext->userId,
                'order.sent_to_kitchen',
                'order',
                $orderUuid,
                ['lines_sent' => count($pendingLines), 'service_window_number' => $windowNumber],
                $auditContext->ipAddress,
            ));

            $this->domainEventBus->dispatch(...$order->pullDomainEvents());
        });
    }
}