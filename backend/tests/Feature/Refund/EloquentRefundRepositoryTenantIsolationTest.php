<?php

declare(strict_types=1);

namespace Tests\Feature\Refund;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Refund\Domain\Entity\Refund;
use App\Refund\Domain\Entity\RefundLine;
use App\Refund\Infrastructure\Persistence\Models\EloquentRefund;
use App\Refund\Infrastructure\Persistence\Repositories\EloquentRefundRepository;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Domain\Exception\SaleLineNotFoundException;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\Shared\Domain\ValueObject\Uuid;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EloquentRefundRepositoryTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_rejects_sales_from_another_restaurant(): void
    {
        [$restaurantA] = $this->createRestaurantStack('a');
        [$restaurantB, $userB] = $this->createRestaurantStack('b');

        ['sale' => $saleA] = $this->createSaleAggregate($restaurantA, 'a');
        $repository = app(EloquentRefundRepository::class);

        $refund = Refund::dddCreate(
            Uuid::generate(),
            $restaurantB->id,
            Uuid::create($saleA->uuid),
            Uuid::create($userB->uuid),
            'partial',
            'cash',
            'tenant check',
            100,
            10,
            110,
        );

        $this->expectException(SaleNotFoundException::class);

        $repository->save($refund);
    }

    public function test_save_rejects_users_from_another_restaurant(): void
    {
        [$restaurantA, $userA] = $this->createRestaurantStack('a');
        [$restaurantB] = $this->createRestaurantStack('b');

        ['sale' => $saleB] = $this->createSaleAggregate($restaurantB, 'b');
        $repository = app(EloquentRefundRepository::class);

        $refund = Refund::dddCreate(
            Uuid::generate(),
            $restaurantB->id,
            Uuid::create($saleB->uuid),
            Uuid::create($userA->uuid),
            'partial',
            'cash',
            'tenant check',
            100,
            10,
            110,
        );

        $this->expectException(UserNotFoundException::class);

        $repository->save($refund);
    }

    public function test_save_line_rejects_sale_lines_from_another_restaurant(): void
    {
        [$restaurantA] = $this->createRestaurantStack('a');
        [$restaurantB, $userB] = $this->createRestaurantStack('b');

        $saleA = $this->createSaleAggregate($restaurantA, 'a');
        ['sale' => $saleB] = $this->createSaleAggregate($restaurantB, 'b');

        $repository = app(EloquentRefundRepository::class);
        $refund = Refund::dddCreate(
            Uuid::generate(),
            $restaurantB->id,
            Uuid::create($saleB->uuid),
            Uuid::create($userB->uuid),
            'partial',
            'cash',
            'tenant check',
            100,
            10,
            110,
        );

        $repository->save($refund);

        $refundLine = RefundLine::dddCreate(
            Uuid::generate(),
            $refund->uuid(),
            Uuid::create($saleA['saleLine']->uuid),
            1,
            100,
            10,
            110,
        );

        $this->expectException(SaleLineNotFoundException::class);

        $repository->saveLine($refundLine);
    }

    /** @return array{0:EloquentRestaurant,1?:EloquentUser} */
    private function createRestaurantStack(string $suffix): array
    {
        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurant '.$suffix,
            'legal_name' => 'Restaurant '.$suffix.' SL',
            'tax_id' => 'B12345'.$suffix.'0',
            'email' => 'restaurant-'.$suffix.'@example.com',
            'password' => Hash::make('secret'),
        ]);

        $user = EloquentUser::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'User '.$suffix,
            'email' => 'user-'.$suffix.'@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'restaurant_id' => $restaurant->id,
            'pin' => '1234',
        ]);

        return [$restaurant, $user];
    }

    /** @return array{sale:EloquentSale,saleLine:EloquentSaleLine} */
    private function createSaleAggregate(EloquentRestaurant $restaurant, string $suffix): array
    {
        $now = now();
        $user = EloquentUser::query()->where('restaurant_id', $restaurant->id)->firstOrFail();

        $zone = EloquentZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Zona '.$suffix,
            'restaurant_id' => $restaurant->id,
        ]);

        $table = EloquentTable::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Mesa '.$suffix,
            'zone_id' => $zone->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $familyId = DB::table('families')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Familia '.$suffix,
            'restaurant_id' => $restaurant->id,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $taxId = DB::table('taxes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA '.$suffix,
            'percentage' => 1000,
            'restaurant_id' => $restaurant->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $product = EloquentProduct::query()->create([
            'uuid' => (string) Str::uuid(),
            'family_id' => $familyId,
            'restaurant_id' => $restaurant->id,
            'tax_id' => $taxId,
            'image_src' => null,
            'name' => 'Producto '.$suffix,
            'price' => 1000,
            'stock' => 100,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $order = EloquentOrder::query()->create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'status' => 'closed',
            'table_id' => $table->id,
            'opened_by_user_id' => $user->id,
            'closed_by_user_id' => $user->id,
            'diners' => 2,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'opened_at' => $now,
            'closed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderLine = EloquentOrderLine::query()->create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'price' => 1000,
            'tax_percentage' => 1000,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'sent_to_kitchen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sale = EloquentSale::query()->create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'ticket_number' => 1,
            'value_date' => $now,
            'subtotal' => 1000,
            'tax_amount' => 100,
            'line_discount_total' => 0,
            'order_discount_total' => 0,
            'total' => 1100,
            'refunded_total' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $saleLine = EloquentSaleLine::query()->create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'sale_id' => $sale->id,
            'order_line_id' => $orderLine->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'price' => 1000,
            'tax_percentage' => 1000,
            'line_subtotal' => 1000,
            'tax_amount' => 100,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'line_total' => 1100,
            'refunded_quantity' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['sale' => $sale, 'saleLine' => $saleLine];
    }
}