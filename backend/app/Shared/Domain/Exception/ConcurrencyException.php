<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class ConcurrencyException extends DomainException
{
    public static function forResource(string $resource, string $uuid): self
    {
        return new self("{$resource} '{$uuid}' was modified by another process. Please reload and try again.");
    }
}