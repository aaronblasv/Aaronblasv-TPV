<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class TaxNotFoundForProductException extends NotFoundException
{
    public function __construct(string $productUuid)
    {
        parent::__construct("Tax not found for product '{$productUuid}'.");
    }
}