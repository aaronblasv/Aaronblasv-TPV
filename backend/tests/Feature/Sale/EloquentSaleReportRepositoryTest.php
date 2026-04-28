<?php

declare(strict_types=1);

namespace Tests\Feature\Sale;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Infrastructure\Persistence\Repositories\EloquentSaleReportRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EloquentSaleReportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_grouped_report_uses_exact_refund_totals_for_products(): void
    {
        $now = now();

        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurant',
            'legal_name' => 'Restaurant SL',
            'tax_id' => 'B11223344',
            'email' => 'restaurant-report@example.com',
            'password' => Hash::make('secret'),
        ]);

        $waiterId = DB::table('users')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Waiter',
            'email' => 'waiter-report@example.com',
            'password' => Hash::make('secret'),
            'role' => 'waiter',
            'restaurant_id' => $restaurant->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sala',
            'restaurant_id' => $restaurant->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $tableId = DB::table('tables')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'zone_id' => $zoneId,
            'restaurant_id' => $restaurant->id,
            'name' => 'Mesa 1',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $familyId = DB::table('families')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Bebidas',
            'restaurant_id' => $restaurant->id,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $taxId = DB::table('taxes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA 10%',
            'percentage' => 10,
            'restaurant_id' => $restaurant->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'family_id' => $familyId,
            'restaurant_id' => $restaurant->id,
            'tax_id' => $taxId,
            'image_src' => null,
            'name' => 'Café',
            'price' => 500,
            'stock' => 100,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'status' => 'closed',
            'table_id' => $tableId,
            'opened_by_user_id' => $waiterId,
            'closed_by_user_id' => $waiterId,
            'diners' => 2,
            'opened_at' => $now,
            'closed_at' => $now,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderLineId = DB::table('order_lines')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_id' => $orderId,
            'product_id' => $productId,
            'user_id' => $waiterId,
            'quantity' => 3,
            'price' => 333,
            'tax_percentage' => 10,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $saleId = DB::table('sales')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_id' => $orderId,
            'user_id' => $waiterId,
            'ticket_number' => 1,
            'value_date' => $now,
            'subtotal' => 909,
            'tax_amount' => 91,
            'line_discount_total' => 0,
            'order_discount_total' => 0,
            'total' => 1000,
            'refunded_total' => 333,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $saleLineId = DB::table('sales_lines')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'sale_id' => $saleId,
            'order_line_id' => $orderLineId,
            'product_name' => 'Café',
            'user_id' => $waiterId,
            'quantity' => 3,
            'price' => 333,
            'tax_percentage' => 10,
            'line_subtotal' => 909,
            'tax_amount' => 91,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'line_total' => 1000,
            'refunded_quantity' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $refundId = DB::table('refunds')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'sale_id' => $saleId,
            'user_id' => $waiterId,
            'type' => 'partial',
            'method' => 'cash',
            'reason' => 'Ajuste',
            'subtotal' => 303,
            'tax_amount' => 30,
            'total' => 333,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('refund_lines')->insert([
            'uuid' => (string) Str::uuid(),
            'refund_id' => $refundId,
            'sale_line_id' => $saleLineId,
            'quantity' => 1,
            'subtotal' => 303,
            'tax_amount' => 30,
            'total' => 333,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $report = (new EloquentSaleReportRepository())->getGroupedReport($restaurant->id, null, null);

        $this->assertCount(1, $report->byProduct);
        $this->assertSame('Café', $report->byProduct[0]->productName);
        $this->assertSame(2, $report->byProduct[0]->totalQuantity);
        $this->assertSame(667, $report->byProduct[0]->total);
    }

    public function test_find_receipt_by_sale_uuid_returns_service_windows_and_snapshots(): void
    {
        $now = now();

        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurant',
            'legal_name' => 'Restaurant SL',
            'tax_id' => 'B55667788',
            'email' => 'restaurant-receipt@example.com',
            'password' => Hash::make('secret'),
        ]);

        $waiterId = DB::table('users')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Laura',
            'email' => 'laura-receipt@example.com',
            'password' => Hash::make('secret'),
            'role' => 'waiter',
            'restaurant_id' => $restaurant->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Terraza',
            'restaurant_id' => $restaurant->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $tableId = DB::table('tables')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'zone_id' => $zoneId,
            'restaurant_id' => $restaurant->id,
            'name' => 'Mesa 7',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $familyId = DB::table('families')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Comida',
            'restaurant_id' => $restaurant->id,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $taxId = DB::table('taxes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA 10%',
            'percentage' => 10,
            'restaurant_id' => $restaurant->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'family_id' => $familyId,
            'restaurant_id' => $restaurant->id,
            'tax_id' => $taxId,
            'image_src' => null,
            'name' => 'Croqueta',
            'price' => 200,
            'stock' => 100,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderUuid = (string) Str::uuid();
        $orderId = DB::table('orders')->insertGetId([
            'uuid' => $orderUuid,
            'restaurant_id' => $restaurant->id,
            'status' => 'closed',
            'table_id' => $tableId,
            'opened_by_user_id' => $waiterId,
            'closed_by_user_id' => $waiterId,
            'diners' => 2,
            'opened_at' => $now,
            'closed_at' => $now,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderLineId = DB::table('order_lines')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_id' => $orderId,
            'product_id' => $productId,
            'user_id' => $waiterId,
            'quantity' => 2,
            'price' => 200,
            'tax_percentage' => 10,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'sent_to_kitchen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $saleUuid = (string) Str::uuid();
        $saleId = DB::table('sales')->insertGetId([
            'uuid' => $saleUuid,
            'restaurant_id' => $restaurant->id,
            'order_id' => $orderId,
            'user_id' => $waiterId,
            'ticket_number' => 25,
            'value_date' => $now,
            'subtotal' => 364,
            'tax_amount' => 36,
            'line_discount_total' => 0,
            'order_discount_total' => 0,
            'total' => 400,
            'refunded_total' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('sales_lines')->insert([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'sale_id' => $saleId,
            'order_line_id' => $orderLineId,
            'product_name' => 'Croqueta',
            'user_id' => $waiterId,
            'quantity' => 2,
            'price' => 200,
            'tax_percentage' => 10,
            'line_subtotal' => 364,
            'tax_amount' => 36,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'line_total' => 400,
            'refunded_quantity' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $windowId = DB::table('order_service_windows')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_id' => $orderId,
            'sent_by_user_id' => $waiterId,
            'sent_by_user_name' => 'Laura',
            'window_number' => 1,
            'sent_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('order_service_window_lines')->insert([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_service_window_id' => $windowId,
            'order_line_id' => $orderLineId,
            'product_name' => 'Croqueta',
            'quantity' => 2,
            'price' => 200,
            'tax_percentage' => 10,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'line_subtotal' => 364,
            'tax_amount' => 36,
            'line_total' => 400,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $receipt = (new EloquentSaleReportRepository())->findReceiptBySaleUuid($restaurant->id, $saleUuid);

        $this->assertNotNull($receipt);
        $this->assertSame('Restaurant', $receipt->restaurantName);
        $this->assertSame('Restaurant SL', $receipt->restaurantLegalName);
        $this->assertSame('B55667788', $receipt->restaurantTaxId);
        $this->assertSame(25, $receipt->ticketNumber);
        $this->assertSame('Mesa 7', $receipt->tableName);
        $this->assertCount(1, $receipt->lines);
        $this->assertSame('Croqueta', $receipt->lines[0]->productName);
        $this->assertCount(1, $receipt->serviceWindows);
        $this->assertSame(1, $receipt->serviceWindows[0]->windowNumber);
        $this->assertSame('Laura', $receipt->serviceWindows[0]->sentByUserName);
        $this->assertCount(1, $receipt->serviceWindows[0]->lines);
        $this->assertSame('Croqueta', $receipt->serviceWindows[0]->lines[0]->productName);
    }
}