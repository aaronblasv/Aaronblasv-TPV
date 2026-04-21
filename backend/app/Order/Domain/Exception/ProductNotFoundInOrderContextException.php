<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class ProductNotFoundInOrderContextException extends NotFoundException
{
    public function __construct(string $productUuid, string $orderUuid)
    {
        parent::__construct("Product '{$productUuid}' not found while adding a line to order '{$orderUuid}'.");
    }
}