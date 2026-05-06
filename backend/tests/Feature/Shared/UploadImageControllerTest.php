<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UploadImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_an_image_and_returns_its_public_url(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $response = $this->post('/api/upload-image', [
            'image' => UploadedFile::fake()->image('avatar.png'),
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['url']);

        $url = $response->json('url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/images/', $url);
        $this->assertCount(1, Storage::disk('public')->allFiles('images'));
    }

    private function createAdmin(): EloquentUser
    {
        $restaurant = EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restaurant',
            'legal_name' => 'Restaurant SL',
            'tax_id' => 'B12345678',
            'email' => 'restaurant-upload@example.com',
            'password' => Hash::make('secret'),
        ]);

        return EloquentUser::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Admin',
            'email' => 'admin-upload@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'restaurant_id' => $restaurant->id,
        ]);
    }
}
