<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginLogoutSessionTest extends TestCase
{
    use RefreshDatabase;

    private const FRONTEND_URL = 'http://localhost:4200';

    public function test_login_creates_session_and_returns_exact_role_payload(): void
    {
        $user = $this->createAdmin();

        $this->withHeader('Origin', self::FRONTEND_URL)
            ->withHeader('Referer', self::FRONTEND_URL.'/login')
            ->get('/sanctum/csrf-cookie')
            ->assertNoContent();

        $response = $this->withHeader('Origin', self::FRONTEND_URL)
            ->withHeader('Referer', self::FRONTEND_URL.'/login')
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'secret',
            ]);

        $response->assertOk()
            ->assertExactJson(['role' => 'admin']);

        $this->assertAuthenticatedAs($user, 'web');
        $response->assertCookie(config('session.cookie'));
    }

    public function test_logout_invalidates_session(): void
    {
        $user = $this->createAdmin();

        $this->actingAs($user, 'web');

        $this->withHeader('Origin', self::FRONTEND_URL)
            ->withHeader('Referer', self::FRONTEND_URL.'/dashboard')
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertGuest('web');
    }

    private function createAdmin(): EloquentUser
    {
        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurant',
            'legal_name' => 'Restaurant SL',
            'tax_id' => 'B12345679',
            'email' => 'restaurant-auth@example.com',
            'password' => Hash::make('secret'),
        ]);

        return EloquentUser::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Admin',
            'email' => 'admin-auth@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'restaurant_id' => $restaurant->id,
        ]);
    }
}
