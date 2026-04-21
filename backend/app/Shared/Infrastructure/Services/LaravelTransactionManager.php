<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Services;

use App\Shared\Domain\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

class LaravelTransactionManager implements TransactionManagerInterface
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}