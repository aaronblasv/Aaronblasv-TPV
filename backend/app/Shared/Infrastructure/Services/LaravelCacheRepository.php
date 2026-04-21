<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Services;

use App\Shared\Domain\CacheRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class LaravelCacheRepository implements CacheRepositoryInterface
{
    public function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        return Cache::remember($key, $ttlSeconds, $callback);
    }
}