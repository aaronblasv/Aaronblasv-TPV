<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface TransactionManagerInterface
{
    public function run(callable $callback): mixed;
}