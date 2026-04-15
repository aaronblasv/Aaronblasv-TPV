<?php

namespace App\Providers;

use App\Dashboard\Domain\Interfaces\DashboardRepositoryInterface;
use App\Dashboard\Infrastructure\Persistence\Repositories\EloquentDashboardRepository;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Infrastructure\Persistence\Repositories\EloquentFamilyRepository;
use App\Invoice\Application\GenerateInvoice\GenerateInvoice;
use App\Invoice\Domain\Interfaces\InvoiceRepositoryInterface;
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
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Repositories\EloquentRestaurantRepository;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Repositories\EloquentSaleRepository;
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
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Dashboard
        $this->app->bind(DashboardRepositoryInterface::class, EloquentDashboardRepository::class);

        // User
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(PasswordHasherInterface::class, LaravelPasswordHasher::class);
        $this->app->bind(TokenGeneratorInterface::class, LaravelTokenGenerator::class);

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
        $this->app->bind(SaleRepositoryInterface::class, EloquentSaleRepository::class);

        // Payment
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(RegisterPayment::class, fn($app) => new RegisterPayment(
            $app->make(PaymentRepositoryInterface::class),
        ));

        // Invoice
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(GenerateInvoice::class, fn($app) => new GenerateInvoice(
            $app->make(InvoiceRepositoryInterface::class),
            $app->make(OrderRepositoryInterface::class),
            $app->make(OrderLineRepositoryInterface::class),
        ));

        // Log
        $this->app->bind(LogRepositoryInterface::class, EloquentLogRepository::class);
        $this->app->bind(CreateLog::class, fn($app) => new CreateLog(
            $app->make(LogRepositoryInterface::class),
        ));
        $this->app->bind(GetLogs::class, fn($app) => new GetLogs(
            $app->make(LogRepositoryInterface::class),
        ));
    }

    public function boot(): void
    {
        //
    }
}
