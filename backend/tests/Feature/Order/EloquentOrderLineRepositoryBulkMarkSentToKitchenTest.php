<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderLineRepository;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EloquentOrderLineRepositoryBulkMarkSentToKitchenTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_mark_sent_to_kitchen_updates_all_pending_lines_in_one_call(): void
    {
        $now = now();

        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurant',
            'legal_name' => 'Restaurant SL',
            'tax_id' => 'B77889900',
            'email' => 'bulk-kitchen@example.com',
            'password' => Hash::make('secret'),
        ]);

        $user = EloquentUser::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Waiter',
            'email' => 'bulk-kitchen-user@example.com',
            'password' => Hash::make('secret'),
            'role' => 'waiter',
            'restaurant_id' => $restaurant->id,
        ]);

        $zone = EloquentZone::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sala',
            'restaurant_id' => $restaurant->id,
        ]);

        $table = EloquentTable::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Mesa 1',
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

        $product = EloquentProduct::query()->create([
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

        $order = EloquentOrder::query()->create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'status' => 'open',
            'table_id' => $table->id,
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

        $firstLine = EloquentOrderLine::query()->create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'price' => 250,
            'tax_percentage' => 1000,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'sent_to_kitchen_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $secondLine = EloquentOrderLine::query()->create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'quantity' => 2,
            'price' => 300,
            'tax_percentage' => 1000,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'sent_to_kitchen_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $alreadySent = EloquentOrderLine::query()->create([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'quantity' => 3,
            'price' => 350,
            'tax_percentage' => 1000,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'sent_to_kitchen_at' => $now->copy()->subMinute(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $repository = new EloquentOrderLineRepository(new EloquentOrderLine(), new EloquentOrder(), new EloquentProduct(), new EloquentUser());
        $sentAt = DomainDateTime::create(new \DateTimeImmutable('2026-04-23 12:34:56'));

        $repository->bulkMarkSentToKitchen([$firstLine->uuid, $secondLine->uuid, $alreadySent->uuid], $restaurant->id, $sentAt);

        $this->assertDatabaseHas('order_lines', [
            'uuid' => $firstLine->uuid,
            'sent_to_kitchen_at' => '2026-04-23 12:34:56',
        ]);

        $this->assertDatabaseHas('order_lines', [
            'uuid' => $secondLine->uuid,
            'sent_to_kitchen_at' => '2026-04-23 12:34:56',
        ]);

        $this->assertDatabaseMissing('order_lines', [
            'uuid' => $alreadySent->uuid,
            'sent_to_kitchen_at' => '2026-04-23 12:34:56',
        ]);
    }
}