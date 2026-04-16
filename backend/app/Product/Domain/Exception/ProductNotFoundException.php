<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class ProductNotFoundException extends NotFoundException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Product '{$uuid}' not found.");
    }
}
