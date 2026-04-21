<?php

namespace Tests\Feature\Persistence;

use App\Order\Domain\Exception\OrderLinePersistenceRelationNotFoundException;
use App\Order\Domain\Exception\OrderPersistenceRelationNotFoundException;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderLineRepository;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use App\Payment\Domain\Exception\PaymentPersistenceRelationNotFoundException;
use App\Payment\Infrastructure\Persistence\Repositories\EloquentPaymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RepositoryOrphanedRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_repository_throws_descriptive_exception_for_orphaned_opened_by_user(): void
    {
        $restaurantId = $this->createRestaurant();
        $openedByUserId = $this->createUser($restaurantId);
        $zoneId = $this->createZone($restaurantId);
        $tableId = $this->createTable($restaurantId, $zoneId);
        $orderUuid = (string) Str::uuid();

        DB::table('orders')->insert([
            'uuid' => $orderUuid,
            'restaurant_id' => $restaurantId,
            'status' => 'open',
            'table_id' => $tableId,
            'opened_by_user_id' => $openedByUserId,
            'closed_by_user_id' => null,
            'diners' => 2,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'opened_at' => now(),
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $openedByUserId)->update(['deleted_at' => now()]);

        $this->expectException(OrderPersistenceRelationNotFoundException::class);
        $this->expectExceptionMessage("references missing opened-by user id '{$openedByUserId}'");

        app(EloquentOrderRepository::class)->findById($orderUuid, $restaurantId);
    }

    public function test_order_line_repository_throws_descriptive_exception_for_orphaned_product(): void
    {
        $restaurantId = $this->createRestaurant();
        $openedByUserId = $this->createUser($restaurantId);
        $zoneId = $this->createZone($restaurantId);
        $tableId = $this->createTable($restaurantId, $zoneId);
        $familyId = $this->createFamily($restaurantId);
        $taxId = $this->createTax($restaurantId);
        $productId = $this->createProduct($restaurantId, $familyId, $taxId);
        $orderId = $this->createOrder($restaurantId, $tableId, $openedByUserId);
        $lineUuid = (string) Str::uuid();

        DB::table('order_lines')->insert([
            'uuid' => $lineUuid,
            'restaurant_id' => $restaurantId,
            'order_id' => $orderId,
            'product_id' => $productId,
            'user_id' => $openedByUserId,
            'quantity' => 1,
            'price' => 1200,
            'tax_percentage' => 10,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'sent_to_kitchen_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->where('id', $productId)->update(['deleted_at' => now()]);

        $this->expectException(OrderLinePersistenceRelationNotFoundException::class);
        $this->expectExceptionMessage("references missing product id '{$productId}'");

        app(EloquentOrderLineRepository::class)->findById($lineUuid, $restaurantId);
    }

    public function test_payment_repository_throws_descriptive_exception_for_orphaned_user(): void
    {
        $restaurantId = $this->createRestaurant();
        $openedByUserId = $this->createUser($restaurantId);
        $zoneId = $this->createZone($restaurantId);
        $tableId = $this->createTable($restaurantId, $zoneId);
        $orderId = $this->createOrder($restaurantId, $tableId, $openedByUserId);
        $orderUuid = DB::table('orders')->where('id', $orderId)->value('uuid');
        $paymentUuid = (string) Str::uuid();

        DB::table('payments')->insert([
            'uuid' => $paymentUuid,
            'order_id' => $orderId,
            'user_id' => $openedByUserId,
            'amount' => 1500,
            'method' => 'cash',
            'description' => 'Pago test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $openedByUserId)->update(['deleted_at' => now()]);

        $this->expectException(PaymentPersistenceRelationNotFoundException::class);
        $this->expectExceptionMessage("references missing user id '{$openedByUserId}'");

        app(EloquentPaymentRepository::class)->findByOrderId($orderUuid);
    }

    private function createRestaurant(): int
    {
        return DB::table('restaurants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Restaurant',
            'legal_name' => 'Test Restaurant SL',
            'tax_id' => 'B00000000',
            'email' => 'restaurant-'.Str::random(6).'@example.com',
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(int $restaurantId): int
    {
        return DB::table('users')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test User',
            'email' => 'user-'.Str::random(6).'@example.com',
            'password' => Hash::make('secret'),
            'role' => 'waiter',
            'image_src' => null,
            'pin' => '1234',
            'restaurant_id' => $restaurantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createZone(int $restaurantId): int
    {
        return DB::table('zones')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sala',
            'restaurant_id' => $restaurantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTable(int $restaurantId, int $zoneId): int
    {
        return DB::table('tables')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'zone_id' => $zoneId,
            'restaurant_id' => $restaurantId,
            'name' => 'Mesa 1',
            'merged_with' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createFamily(int $restaurantId): int
    {
        return DB::table('families')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Entrantes',
            'restaurant_id' => $restaurantId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTax(int $restaurantId): int
    {
        return DB::table('taxes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA 10%',
            'percentage' => 10,
            'restaurant_id' => $restaurantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createProduct(int $restaurantId, int $familyId, int $taxId): int
    {
        return DB::table('products')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'family_id' => $familyId,
            'restaurant_id' => $restaurantId,
            'tax_id' => $taxId,
            'image_src' => null,
            'name' => 'Producto test',
            'price' => 1200,
            'stock' => 50,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrder(int $restaurantId, int $tableId, int $openedByUserId): int
    {
        return DB::table('orders')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'status' => 'open',
            'table_id' => $tableId,
            'opened_by_user_id' => $openedByUserId,
            'closed_by_user_id' => null,
            'diners' => 2,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'opened_at' => now(),
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
