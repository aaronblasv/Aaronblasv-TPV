<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequireBackofficeRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'backoffice'])
            ->get('/api/test-backoffice-protected', fn () => response()->json(['ok' => true]));
    }

    public function test_waiter_cannot_access_backoffice_route(): void
    {
        $user = $this->createUser('waiter');
        Sanctum::actingAs($user);

        $this->getJson('/api/test-backoffice-protected')
            ->assertStatus(403)
            ->assertJson(['message' => 'No autorizado.']);
    }

    public function test_admin_can_access_backoffice_route(): void
    {
        $user = $this->createUser('admin');
        Sanctum::actingAs($user);

        $this->getJson('/api/test-backoffice-protected')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    private function createUser(string $role): EloquentUser
    {
        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurant',
            'legal_name' => 'Restaurant SL',
            'tax_id' => 'B12345670',
            'email' => Str::lower($role).'@example.com',
            'password' => Hash::make('secret'),
        ]);

        return EloquentUser::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => ucfirst($role),
            'email' => Str::lower($role).'+user@example.com',
            'password' => Hash::make('secret'),
            'role' => $role,
            'restaurant_id' => $restaurant->id,
        ]);
    }
}