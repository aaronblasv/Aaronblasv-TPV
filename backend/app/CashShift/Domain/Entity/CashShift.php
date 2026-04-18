<?php

declare(strict_types=1);

namespace App\CashShift\Domain\Entity;

use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class CashShift
{
    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private string $status,
        private int $openingCash,
        private int $cashTotal,
        private int $cardTotal,
        private int $bizumTotal,
        private int $refundTotal,
        private ?int $countedCash,
        private int $cashDifference,
        private ?string $notes,
        private \DateTimeImmutable $openedAt,
        private ?\DateTimeImmutable $closedAt,
    ) {}

    public static function open(Uuid $uuid, int $restaurantId, Uuid $openedByUserId, int $openingCash, ?string $notes): self
    {
        return new self(
            $uuid,
            RestaurantId::create($restaurantId),
            $openedByUserId,
            null,
            'open',
            $openingCash,
            0,
            0,
            0,
            0,
            null,
            0,
            $notes,
            new \DateTimeImmutable(),
            null,
        );
    }

    public static function fromPersistence(
        string $uuid,
        int $restaurantId,
        string $openedByUserId,
        ?string $closedByUserId,
        string $status,
        int $openingCash,
        int $cashTotal,
        int $cardTotal,
        int $bizumTotal,
        int $refundTotal,
        ?int $countedCash,
        int $cashDifference,
        ?string $notes,
        \DateTimeImmutable $openedAt,
        ?\DateTimeImmutable $closedAt,
    ): self {
        return new self(
            Uuid::create($uuid),
            RestaurantId::create($restaurantId),
            Uuid::create($openedByUserId),
            $closedByUserId ? Uuid::create($closedByUserId) : null,
            $status,
            $openingCash,
            $cashTotal,
            $cardTotal,
            $bizumTotal,
            $refundTotal,
            $countedCash,
            $cashDifference,
            $notes,
            $openedAt,
            $closedAt,
        );
    }

    public function close(Uuid $closedByUserId, int $cashTotal, int $cardTotal, int $bizumTotal, int $refundTotal, int $countedCash, ?string $notes): void
    {
        $this->closedByUserId = $closedByUserId;
        $this->status = 'closed';
        $this->cashTotal = $cashTotal;
        $this->cardTotal = $cardTotal;
        $this->bizumTotal = $bizumTotal;
        $this->refundTotal = $refundTotal;
        $this->countedCash = $countedCash;
        $this->cashDifference = $countedCash - ($this->openingCash + $cashTotal);
        $this->notes = $notes;
        $this->closedAt = new \DateTimeImmutable();
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function openedByUserId(): Uuid { return $this->openedByUserId; }
    public function closedByUserId(): ?Uuid { return $this->closedByUserId; }
    public function status(): string { return $this->status; }
    public function openingCash(): int { return $this->openingCash; }
    public function cashTotal(): int { return $this->cashTotal; }
    public function cardTotal(): int { return $this->cardTotal; }
    public function bizumTotal(): int { return $this->bizumTotal; }
    public function refundTotal(): int { return $this->refundTotal; }
    public function countedCash(): ?int { return $this->countedCash; }
    public function cashDifference(): int { return $this->cashDifference; }
    public function notes(): ?string { return $this->notes; }
    public function openedAt(): \DateTimeImmutable { return $this->openedAt; }
    public function closedAt(): ?\DateTimeImmutable { return $this->closedAt; }
}