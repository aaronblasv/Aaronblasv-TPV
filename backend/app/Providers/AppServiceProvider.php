<?php

declare(strict_types=1);

namespace App\Providers;

use App\CashShift\Domain\Interfaces\CashShiftRepositoryInterface;
use App\CashShift\Domain\Interfaces\CashShiftSalesReadModelInterface;
use App\CashShift\Infrastructure\Persistence\Repositories\EloquentCashShiftRepository;
use App\CashShift\Infrastructure\Persistence\ReadModels\EloquentCashShiftSalesReadModel;
use App\Dashboard\Domain\Interfaces\DashboardRepositoryInterface;
use App\Dashboard\Infrastructure\Persistence\Repositories\CachedDashboardRepository;
use App\Dashboard\Infrastructure\Persistence\Repositories\EloquentDashboardRepository;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Infrastructure\Persistence\Repositories\EloquentFamilyRepository;
use App\Invoice\Application\GenerateInvoice\GenerateInvoice;
use App\Invoice\Domain\Interfaces\InvoiceOrderDataProviderInterface;
use App\Invoice\Domain\Interfaces\InvoiceRepositoryInterface;
use App\Invoice\Infrastructure\Persistence\Providers\EloquentInvoiceOrderDataProvider;
use App\Invoice\Infrastructure\Persistence\Repositories\EloquentInvoiceRepository;
use App\Log\Application\CreateLog\CreateLog;
use App\Log\Application\GetLogs\GetLogs;
use App\Log\Domain\Interfaces\LogRepositoryInterface;
use App\Log\Infrastructure\Persistence\Repositories\EloquentLogRepository;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderLineRepository;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use App\Payment\Application\RegisterPayment\RegisterPayment;
use App\Payment\Domain\Interfaces\PaymentRepositoryInterface;
use App\Payment\Infrastructure\Persistence\Repositories\EloquentPaymentRepository;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use App\Refund\Domain\Interfaces\RefundRepositoryInterface;
use App\Refund\Infrastructure\Persistence\Repositories\EloquentRefundRepository;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Repositories\EloquentRestaurantRepository;
use App\Sale\Domain\Interfaces\SaleReadRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleReportRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleWriteRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Repositories\EloquentSaleReadRepository;
use App\Sale\Infrastructure\Persistence\Repositories\EloquentSaleReportRepository;
use App\Sale\Infrastructure\Persistence\Repositories\EloquentSaleWriteRepository;
use App\Table\Domain\Interfaces\TableRepositoryInterface;
use App\Table\Infrastructure\Persistence\Repositories\EloquentTableRepository;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Infrastructure\Persistence\Repositories\EloquentTaxRepository;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\TokenGeneratorInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\User\Infrastructure\Services\LaravelPasswordHasher;
use App\User\Infrastructure\Services\LaravelTokenGenerator;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Infrastructure\Persistence\Repositories\EloquentZoneRepository;
use App\Log\Infrastructure\Listener\WriteLogOnActionLogged;
use App\Order\Domain\Event\OrderClosed;
use App\Sale\Application\CreateSaleOnOrderClosed\CreateSaleOnOrderClosed;
use App\Shared\Domain\Event\ActionLogged;
use App\Shared\Domain\CacheRepositoryInterface;
use App\Shared\Domain\TransactionManagerInterface;
use App\Shared\Domain\Interfaces\DomainEventBusInterface;
use App\Shared\Domain\Interfaces\ImageUploaderInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface as LegacyTransactionManagerInterface;
use App\Shared\Application\UploadImage\UploadImage;
use App\Shared\Infrastructure\Services\LaravelCacheRepository;
use App\Shared\Infrastructure\Services\LaravelDomainEventBus;
use App\Shared\Infrastructure\Services\LaravelTransactionManager;
use App\Shared\Infrastructure\Services\PublicStorageImageUploader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use App\User\Domain\Interfaces\PinGeneratorInterface;
use App\User\Infrastructure\Services\RandomPinGenerator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Dashboard
        $this->app->bind(DashboardRepositoryInterface::class, CachedDashboardRepository::class);

        // User
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(PasswordHasherInterface::class, LaravelPasswordHasher::class);
        $this->app->bind(TokenGeneratorInterface::class, LaravelTokenGenerator::class);
        $this->app->bind(PinGeneratorInterface::class, RandomPinGenerator::class);

        // Restaurant
        $this->app->bind(RestaurantRepositoryInterface::class, EloquentRestaurantRepository::class);

        // Catalogue
        $this->app->bind(TaxRepositoryInterface::class, EloquentTaxRepository::class);
        $this->app->bind(FamilyRepositoryInterface::class, EloquentFamilyRepository::class);
        $this->app->bind(ZoneRepositoryInterface::class, EloquentZoneRepository::class);
        $this->app->bind(TableRepositoryInterface::class, EloquentTableRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);

        // Order
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(OrderLineRepositoryInterface::class, EloquentOrderLineRepository::class);

        // Sale
        $this->app->bind(SaleWriteRepositoryInterface::class, EloquentSaleWriteRepository::class);
        $this->app->bind(SaleReadRepositoryInterface::class, EloquentSaleReadRepository::class);
        $this->app->bind(SaleReportRepositoryInterface::class, EloquentSaleReportRepository::class);

        // Payment
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(RegisterPayment::class, fn($app) => new RegisterPayment(
            $app->make(PaymentRepositoryInterface::class),
            $app->make(TransactionManagerInterface::class),
            $app->make(DomainEventBusInterface::class),
        ));

        // Invoice
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(InvoiceOrderDataProviderInterface::class, EloquentInvoiceOrderDataProvider::class);
        $this->app->bind(GenerateInvoice::class, fn($app) => new GenerateInvoice(
            $app->make(InvoiceRepositoryInterface::class),
            $app->make(InvoiceOrderDataProviderInterface::class),
            $app->make(TransactionManagerInterface::class),
            $app->make(DomainEventBusInterface::class),
        ));

        // Log
        $this->app->bind(LogRepositoryInterface::class, EloquentLogRepository::class);
        $this->app->bind(CreateLog::class, fn($app) => new CreateLog(
            $app->make(LogRepositoryInterface::class),
        ));
        $this->app->bind(GetLogs::class, fn($app) => new GetLogs(
            $app->make(LogRepositoryInterface::class),
        ));

        // Refund
        $this->app->bind(RefundRepositoryInterface::class, EloquentRefundRepository::class);

        // Cash shift
        $this->app->bind(CashShiftRepositoryInterface::class, EloquentCashShiftRepository::class);
        $this->app->bind(CashShiftSalesReadModelInterface::class, EloquentCashShiftSalesReadModel::class);

        // Shared
        $this->app->bind(DomainEventBusInterface::class, LaravelDomainEventBus::class);
        $this->app->bind(CacheRepositoryInterface::class, LaravelCacheRepository::class);
        $this->app->bind(ImageUploaderInterface::class, PublicStorageImageUploader::class);
        $this->app->bind(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->bind(LegacyTransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->bind(UploadImage::class, fn($app) => new UploadImage(
            $app->make(ImageUploaderInterface::class),
        ));
    }

    public function boot(): void
    {
        Event::listen(ActionLogged::class, WriteLogOnActionLogged::class);
        Event::listen(OrderClosed::class, CreateSaleOnOrderClosed::class);
    }
}
