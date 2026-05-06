<?php

declare(strict_types=1);

namespace Tests\Feature\Tax;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaxUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_duplicate_tax_name_in_same_restaurant(): void
    {
        $admin = $this->createAdmin();

        EloquentTax::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA General',
            'percentage' => 2100,
            'restaurant_id' => $admin->restaurant_id,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/taxes', [
            'name' => '  iva general  ',
            'percentage' => 10,
        ])
            ->assertStatus(422)
            ->assertJson(['message' => "Tax name 'iva general' already exists."]);
    }

    public function test_can_create_same_tax_name_in_another_restaurant(): void
    {
        $firstAdmin = $this->createAdmin('first');
        $secondAdmin = $this->createAdmin('second');

        EloquentTax::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA Reducido',
            'percentage' => 1000,
            'restaurant_id' => $firstAdmin->restaurant_id,
        ]);

        Sanctum::actingAs($secondAdmin);

        $this->postJson('/api/taxes', [
            'name' => 'IVA Reducido',
            'percentage' => 10,
        ])
            ->assertStatus(201)
            ->assertJson([
                'name' => 'IVA Reducido',
                'percentage' => 10.0,
            ]);
    }

    public function test_cannot_update_tax_to_duplicate_name_in_same_restaurant(): void
    {
        $admin = $this->createAdmin();

        $firstTax = EloquentTax::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA General',
            'percentage' => 2100,
            'restaurant_id' => $admin->restaurant_id,
        ]);

        $secondTax = EloquentTax::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA Reducido',
            'percentage' => 1000,
            'restaurant_id' => $admin->restaurant_id,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson('/api/taxes/'.$secondTax->uuid, [
            'name' => ' iva general ',
            'percentage' => 4,
        ])
            ->assertStatus(422)
            ->assertJson(['message' => "Tax name 'iva general' already exists."]);

        $this->assertSame('IVA Reducido', $secondTax->fresh()->name);
        $this->assertSame(1000, $secondTax->fresh()->percentage);
        $this->assertNotSame($firstTax->uuid, $secondTax->uuid);
    }

    public function test_can_reuse_tax_name_after_soft_delete(): void
    {
        $admin = $this->createAdmin();

        $tax = EloquentTax::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA Temporal',
            'percentage' => 500,
            'restaurant_id' => $admin->restaurant_id,
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson('/api/taxes/'.$tax->uuid)
            ->assertStatus(204);

        $this->postJson('/api/taxes', [
            'name' => 'IVA Temporal',
            'percentage' => 7.5,
        ])
            ->assertStatus(201)
            ->assertJson([
                'name' => 'IVA Temporal',
                'percentage' => 7.5,
            ]);
    }

    private function createAdmin(string $suffix = 'default'): EloquentUser
    {
        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurant '.$suffix,
            'legal_name' => 'Restaurant '.$suffix.' SL',
            'tax_id' => 'B'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => 'restaurant-'.$suffix.'@example.com',
            'password' => Hash::make('secret'),
        ]);

        return EloquentUser::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Admin '.$suffix,
            'email' => 'admin-'.$suffix.'@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'restaurant_id' => $restaurant->id,
        ]);
    }
}
