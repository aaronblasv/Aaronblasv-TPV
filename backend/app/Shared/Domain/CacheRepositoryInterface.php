<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface CacheRepositoryInterface
{
    public function remember(string $key, int $ttlSeconds, callable $callback): mixed;
}