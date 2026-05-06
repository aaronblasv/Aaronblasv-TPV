<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Order\Application\GetAllOpenOrders\GetAllOpenOrders;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderLineRepository;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetAllOpenOrdersPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_open_orders_loads_lines_without_n_plus_one_queries(): void
    {
        $now = now();

        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurant',
            'legal_name' => 'Restaurant SL',
            'tax_id' => 'B99887766',
            'email' => 'restaurant-open-orders@example.com',
            'password' => Hash::make('secret'),
        ]);

        $user = EloquentUser::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Waiter',
            'email' => 'waiter-open-orders@example.com',
            'password' => Hash::make('secret'),
            'role' => 'waiter',
            'restaurant_id' => $restaurant->id,
        ]);

        $zone = EloquentZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sala',
            'restaurant_id' => $restaurant->id,
        ]);

        $tableOne = EloquentTable::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Mesa 1',
            'zone_id' => $zone->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $tableTwo = EloquentTable::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Mesa 2',
            'zone_id' => $zone->id,
            'restaurant_id' => $restaurant->id,
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
            'percentage' => 1000,
            'restaurant_id' => $restaurant->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productOneId = EloquentProduct::query()->insertGetId([
            'uuid' => (string) Str::uuid(),
            'family_id' => $familyId,
            'restaurant_id' => $restaurant->id,
            'tax_id' => $taxId,
            'image_src' => null,
            'name' => 'Café',
            'price' => 250,
            'stock' => 100,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productTwoId = EloquentProduct::query()->insertGetId([
            'uuid' => (string) Str::uuid(),
            'family_id' => $familyId,
            'restaurant_id' => $restaurant->id,
            'tax_id' => $taxId,
            'image_src' => null,
            'name' => 'Té',
            'price' => 300,
            'stock' => 100,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderOneId = DB::table('orders')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'status' => 'open',
            'table_id' => $tableOne->id,
            'opened_by_user_id' => $user->id,
            'closed_by_user_id' => null,
            'diners' => 2,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'opened_at' => $now,
            'closed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderTwoId = DB::table('orders')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'status' => 'open',
            'table_id' => $tableTwo->id,
            'opened_by_user_id' => $user->id,
            'closed_by_user_id' => null,
            'diners' => 4,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'opened_at' => $now,
            'closed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('order_lines')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'restaurant_id' => $restaurant->id,
                'order_id' => $orderOneId,
                'product_id' => $productOneId,
                'user_id' => $user->id,
                'quantity' => 1,
                'price' => 250,
                'tax_percentage' => 1000,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'restaurant_id' => $restaurant->id,
                'order_id' => $orderTwoId,
                'product_id' => $productTwoId,
                'user_id' => $user->id,
                'quantity' => 2,
                'price' => 300,
                'tax_percentage' => 1000,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $useCase = new GetAllOpenOrders(
            new EloquentOrderRepository(new EloquentOrder, new EloquentTable, new EloquentUser),
            new EloquentOrderLineRepository(new EloquentOrderLine, new EloquentOrder, new EloquentProduct, new EloquentUser),
        );

        $warmup = $useCase($restaurant->id);
        $this->assertCount(2, $warmup);

        $queries = [];
        DB::listen(static function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $response = $useCase($restaurant->id);

        $this->assertCount(2, $response);

        $orderLineSelects = array_values(array_filter($queries, static function (string $sql): bool {
            $normalized = strtolower(trim($sql));

            return str_starts_with($normalized, 'select')
                && str_contains($normalized, 'order_lines');
        }));

        $this->assertCount(1, $orderLineSelects);
    }
}
