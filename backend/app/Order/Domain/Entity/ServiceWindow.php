<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class ServiceWindow
{
    /**
     * @param ServiceWindowLine[] $lines
     */
    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private Uuid $orderId,
        private Uuid $sentByUserId,
        private string $sentByUserName,
        private int $windowNumber,
        private DomainDateTime $sentAt,
        private array $lines,
    ) {}

    /**
     * @param ServiceWindowLine[] $lines
     */
    public static function dddCreate(
        Uuid $uuid,
        int $restaurantId,
        Uuid $orderId,
        Uuid $sentByUserId,
        string $sentByUserName,
        int $windowNumber,
        DomainDateTime $sentAt,
        array $lines,
    ): self {
        return new self(
            $uuid,
            RestaurantId::create($restaurantId),
            $orderId,
            $sentByUserId,
            $sentByUserName,
            $windowNumber,
            $sentAt,
            $lines,
        );
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function restaurantId(): int
    {
        return $this->restaurantId->getValue();
    }

    public function orderId(): Uuid
    {
        return $this->orderId;
    }

    public function sentByUserId(): Uuid
    {
        return $this->sentByUserId;
    }

    public function sentByUserName(): string
    {
        return $this->sentByUserName;
    }

    public function windowNumber(): int
    {
        return $this->windowNumber;
    }

    public function sentAt(): DomainDateTime
    {
        return $this->sentAt;
    }

    /**
     * @return ServiceWindowLine[]
     */
    public function lines(): array
    {
        return $this->lines;
    }
}
