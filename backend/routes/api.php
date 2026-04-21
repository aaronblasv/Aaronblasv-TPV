<?php

use Illuminate\Support\Facades\Route;
use App\Tax\Infrastructure\Entrypoint\Http\GetAllTaxesController;
use App\Tax\Infrastructure\Entrypoint\Http\CreateTaxController;
use App\Tax\Infrastructure\Entrypoint\Http\UpdateTaxController;
use App\Tax\Infrastructure\Entrypoint\Http\DeleteTaxController;
use App\Family\Infrastructure\Entrypoint\Http\GetAllFamiliesController;
use App\Family\Infrastructure\Entrypoint\Http\CreateFamilyController;
use App\Family\Infrastructure\Entrypoint\Http\UpdateFamilyController;
use App\Family\Infrastructure\Entrypoint\Http\DeleteFamilyController;
use App\Family\Infrastructure\Entrypoint\Http\ActivateFamilyController;
use App\Family\Infrastructure\Entrypoint\Http\DeactivateFamilyController;
use App\Zone\Infrastructure\Entrypoint\Http\GetAllZonesController;
use App\Zone\Infrastructure\Entrypoint\Http\CreateZoneController;
use App\Zone\Infrastructure\Entrypoint\Http\UpdateZoneController;
use App\Zone\Infrastructure\Entrypoint\Http\DeleteZoneController;
use App\Table\Infrastructure\Entrypoint\Http\GetAllTablesController;
use App\Table\Infrastructure\Entrypoint\Http\CreateTableController;
use App\Table\Infrastructure\Entrypoint\Http\UpdateTableController;
use App\Table\Infrastructure\Entrypoint\Http\DeleteTableController;
use App\Product\Infrastructure\Entrypoint\Http\GetAllProductsController;
use App\Product\Infrastructure\Entrypoint\Http\CreateProductController;
use App\Product\Infrastructure\Entrypoint\Http\UpdateProductController;
use App\Product\Infrastructure\Entrypoint\Http\DeleteProductController;
use App\Product\Infrastructure\Entrypoint\Http\ActivateProductController;
use App\Product\Infrastructure\Entrypoint\Http\DeactivateProductController;
use App\User\Infrastructure\Entrypoint\Http\LoginController;
use App\User\Infrastructure\Entrypoint\Http\LogoutController;
use App\User\Infrastructure\Entrypoint\Http\GetAuthenticatedUserController;
use App\User\Infrastructure\Entrypoint\Http\GetAllUsersController;
use App\User\Infrastructure\Entrypoint\Http\GetUserByIdController;
use App\User\Infrastructure\Entrypoint\Http\CreateUserController;
use App\User\Infrastructure\Entrypoint\Http\UpdateUserController;
use App\User\Infrastructure\Entrypoint\Http\DeleteUserController;
use App\Order\Infrastructure\Entrypoint\Http\OpenOrderController;
use App\Order\Infrastructure\Entrypoint\Http\GetOrderByTableController;
use App\Order\Infrastructure\Entrypoint\Http\AddOrderLineController;
use App\Order\Infrastructure\Entrypoint\Http\UpdateOrderLineQuantityController;
use App\Order\Infrastructure\Entrypoint\Http\UpdateOrderLineDiscountController;
use App\Order\Infrastructure\Entrypoint\Http\RemoveOrderLineController;
use App\Order\Infrastructure\Entrypoint\Http\UpdateOrderDinersController;
use App\Order\Infrastructure\Entrypoint\Http\UpdateOrderDiscountController;
use App\Order\Infrastructure\Entrypoint\Http\SendOrderToKitchenController;
use App\Order\Infrastructure\Entrypoint\Http\CloseOrderController;
use App\Order\Infrastructure\Entrypoint\Http\CancelOrderController;
use App\Order\Infrastructure\Entrypoint\Http\GetAllOpenOrdersController;
use App\Order\Infrastructure\Entrypoint\Http\TransferOrderTableController;
use App\User\Infrastructure\Entrypoint\Http\ValidatePinController;
use App\User\Infrastructure\Entrypoint\Http\UpdateUserPhotoController;
use App\Payment\Infrastructure\Entrypoint\Http\RegisterPaymentController;
use App\Invoice\Infrastructure\Entrypoint\Http\GenerateInvoiceController;
use App\Log\Infrastructure\Entrypoint\Http\GetLogsController;
use App\Sale\Infrastructure\Entrypoint\Http\GetAllSalesController;
use App\Sale\Infrastructure\Entrypoint\Http\GetSaleLinesController;
use App\Sale\Infrastructure\Entrypoint\Http\GetSalesReportController;
use App\Dashboard\Infrastructure\Entrypoint\Http\DashboardController;
use App\Refund\Infrastructure\Entrypoint\Http\CreateRefundController;
use App\CashShift\Infrastructure\Entrypoint\Http\OpenCashShiftController;
use App\CashShift\Infrastructure\Entrypoint\Http\GetCurrentCashShiftController;
use App\CashShift\Infrastructure\Entrypoint\Http\CloseCashShiftController;
use App\Table\Infrastructure\Entrypoint\Http\MergeTablesController;
use App\Table\Infrastructure\Entrypoint\Http\UnmergeTablesController;
use App\Shared\Infrastructure\Entrypoint\Http\UploadImageController;

Route::post('/auth/login', LoginController::class);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/logout', LogoutController::class);
    Route::get('/auth/me', GetAuthenticatedUserController::class);
    Route::post('/upload-image', UploadImageController::class);

    Route::post('/orders', OpenOrderController::class);
    Route::get('/tables/{tableUuid}/order', GetOrderByTableController::class);
    Route::post('/orders/{orderUuid}/lines', AddOrderLineController::class);
    Route::get('/orders/open', GetAllOpenOrdersController::class);
    Route::post('/orders/{orderUuid}/payments', RegisterPaymentController::class);
    Route::post('/orders/{orderUuid}/generate-invoice', GenerateInvoiceController::class);
    Route::put('/orders/{orderUuid}/lines/{lineUuid}', UpdateOrderLineQuantityController::class);
    Route::patch('/orders/{orderUuid}/lines/{lineUuid}/discount', UpdateOrderLineDiscountController::class);
    Route::delete('/orders/{orderUuid}/lines/{lineUuid}', RemoveOrderLineController::class);
    Route::patch('/orders/{orderUuid}/diners', UpdateOrderDinersController::class);
    Route::patch('/orders/{orderUuid}/discount', UpdateOrderDiscountController::class);
    Route::post('/orders/{orderUuid}/send-to-kitchen', SendOrderToKitchenController::class);
    Route::patch('/orders/{orderUuid}/transfer', TransferOrderTableController::class);
    Route::post('/orders/{orderUuid}/close', CloseOrderController::class);
    Route::delete('/orders/{orderUuid}', CancelOrderController::class);

    Route::get('/tpv/zones', GetAllZonesController::class);
    Route::get('/tpv/tables', GetAllTablesController::class);
    Route::get('/tpv/products', GetAllProductsController::class);
    Route::get('/tpv/users', GetAllUsersController::class);
    Route::post('/tpv/validate-pin', ValidatePinController::class);
    Route::get('/tpv/families', GetAllFamiliesController::class);
    Route::get('/tpv/taxes', GetAllTaxesController::class);
    Route::post('/tpv/tables/{tableUuid}/merge', MergeTablesController::class);
    Route::post('/tpv/tables/{tableUuid}/unmerge', UnmergeTablesController::class);
    Route::patch('/tpv/users/{uuid}/photo', UpdateUserPhotoController::class);
});

Route::middleware(['auth:sanctum', 'backoffice'])->group(function () {
    Route::get('/dashboard', DashboardController::class);

    Route::get('/taxes', GetAllTaxesController::class);
    Route::post('/taxes', CreateTaxController::class);
    Route::put('/taxes/{uuid}', UpdateTaxController::class);
    Route::delete('/taxes/{uuid}', DeleteTaxController::class);

    Route::get('/families', GetAllFamiliesController::class);
    Route::post('/families', CreateFamilyController::class);
    Route::put('/families/{uuid}', UpdateFamilyController::class);
    Route::delete('/families/{uuid}', DeleteFamilyController::class);
    Route::patch('/families/{uuid}/activate', ActivateFamilyController::class);
    Route::patch('/families/{uuid}/deactivate', DeactivateFamilyController::class);

    Route::get('/zones', GetAllZonesController::class);
    Route::post('/zones', CreateZoneController::class);
    Route::put('/zones/{uuid}', UpdateZoneController::class);
    Route::delete('/zones/{uuid}', DeleteZoneController::class);

    Route::get('/tables', GetAllTablesController::class);
    Route::post('/tables', CreateTableController::class);
    Route::put('/tables/{uuid}', UpdateTableController::class);
    Route::delete('/tables/{uuid}', DeleteTableController::class);

    Route::get('/products', GetAllProductsController::class);
    Route::post('/products', CreateProductController::class);
    Route::put('/products/{uuid}', UpdateProductController::class);
    Route::delete('/products/{uuid}', DeleteProductController::class);
    Route::patch('/products/{uuid}/activate', ActivateProductController::class);
    Route::patch('/products/{uuid}/deactivate', DeactivateProductController::class);

    Route::get('/users', GetAllUsersController::class);
    Route::get('/users/{uuid}', GetUserByIdController::class);
    Route::post('/users', CreateUserController::class);
    Route::put('/users/{uuid}', UpdateUserController::class);
    Route::delete('/users/{uuid}', DeleteUserController::class);

    Route::get('/logs', GetLogsController::class);

    Route::get('/sales', GetAllSalesController::class);
    Route::get('/sales/report', GetSalesReportController::class);
    Route::get('/sales/{uuid}/lines', GetSaleLinesController::class);
    Route::post('/sales/{saleUuid}/refunds', CreateRefundController::class);

    Route::get('/cash-shifts/current', GetCurrentCashShiftController::class);
    Route::post('/cash-shifts', OpenCashShiftController::class)->middleware('require.role:admin,supervisor');
    Route::post('/cash-shifts/{cashShiftUuid}/close', CloseCashShiftController::class)->middleware('require.role:admin,supervisor');
});