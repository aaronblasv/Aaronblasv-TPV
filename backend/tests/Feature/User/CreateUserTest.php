<?php

namespace Tests\Feature\User;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_users_returns_201_and_user_json(): void
    {
        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Restaurant',
            'legal_name' => 'Test Restaurant SL',
            'tax_id' => 'B00000000',
            'email' => 'restaurant@example.com',
            'password' => Hash::make('secret'),
        ]);

        $admin = EloquentUser::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'restaurant_id' => $restaurant->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'Integration User',
            'email' => 'integration@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'waiter',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
            'pin',
            'created_at',
            'updated_at',
        ]);
        $response->assertJson([
            'name' => 'Integration User',
            'email' => 'integration@example.com',
        ]);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->json('id')
        );
    }
}
