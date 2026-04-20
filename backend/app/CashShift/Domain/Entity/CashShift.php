<?php

declare(strict_types=1);

namespace App\CashShift\Domain\Entity;

use App\CashShift\Domain\Exception\CashShiftAlreadyClosedException;
use App\CashShift\Domain\Exception\InvalidCashShiftCloseException;
use App\CashShift\Domain\ValueObject\ClosingCashSnapshot;
use App\CashShift\Domain\ValueObject\CashShiftStatus;
use App\Shared\Domain\Interfaces\HasDomainEventsInterface;
use App\Shared\Domain\Support\RecordsDomainEvents;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class CashShift implements HasDomainEventsInterface
{
    use RecordsDomainEvents;

    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private CashShiftStatus $status,
        private Money $openingCash,
        private Money $cashTotal,
        private Money $cardTotal,
        private Money $bizumTotal,
        private Money $refundTotal,
        private ?Money $countedCash,
        private Money $cashDifference,
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
            CashShiftStatus::OPEN,
            Money::create($openingCash),
            Money::zero(),
            Money::zero(),
            Money::zero(),
            Money::zero(),
            null,
            Money::zero(),
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
            CashShiftStatus::create($status),
            Money::create($openingCash),
            Money::create($cashTotal),
            Money::create($cardTotal),
            Money::create($bizumTotal),
            Money::create($refundTotal),
            $countedCash !== null ? Money::create($countedCash) : null,
            Money::create($cashDifference),
            $notes,
            $openedAt,
            $closedAt,
        );
    }

    public function close(ClosingCashSnapshot $snapshot): void
    {
        if (!$this->status->isOpen()) {
            throw new CashShiftAlreadyClosedException();
        }

        $countedCashMoney = Money::create($snapshot->countedCash);
        if ($countedCashMoney->isNegative()) {
            throw new InvalidCashShiftCloseException('Counted cash cannot be negative.');
        }

        $closedAt = new \DateTimeImmutable();
        if ($closedAt <= $this->openedAt) {
            throw new InvalidCashShiftCloseException('Closed date must be after opened date.');
        }

        $this->closedByUserId = $snapshot->closedByUserId;
        if (!$this->status->canTransitionTo(CashShiftStatus::CLOSED)) {
            throw new CashShiftAlreadyClosedException();
        }
        $this->status = CashShiftStatus::CLOSED;
        $this->cashTotal = Money::create($snapshot->cashTotal);
        $this->cardTotal = Money::create($snapshot->cardTotal);
        $this->bizumTotal = Money::create($snapshot->bizumTotal);
        $this->refundTotal = Money::create($snapshot->refundTotal);
        $this->countedCash = $countedCashMoney;
        $this->cashDifference = $countedCashMoney->subtract($this->openingCash->add($this->cashTotal));
        $this->notes = $snapshot->notes;
        $this->closedAt = $closedAt;
    }

    public function id(): Uuid { return $this->uuid; }
    public function uuid(): Uuid { return $this->id(); }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function openedByUserId(): Uuid { return $this->openedByUserId; }
    public function closedByUserId(): ?Uuid { return $this->closedByUserId; }
    public function status(): CashShiftStatus { return $this->status; }
    public function openingCash(): Money { return $this->openingCash; }
    public function cashTotal(): Money { return $this->cashTotal; }
    public function cardTotal(): Money { return $this->cardTotal; }
    public function bizumTotal(): Money { return $this->bizumTotal; }
    public function refundTotal(): Money { return $this->refundTotal; }
    public function countedCash(): ?Money { return $this->countedCash; }
    public function cashDifference(): Money { return $this->cashDifference; }
    public function notes(): ?string { return $this->notes; }
    public function openedAt(): \DateTimeImmutable { return $this->openedAt; }
    public function closedAt(): ?\DateTimeImmutable { return $this->closedAt; }
}