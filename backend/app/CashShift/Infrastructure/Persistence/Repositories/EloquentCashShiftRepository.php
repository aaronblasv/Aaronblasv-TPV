<?php

declare(strict_types=1);

namespace App\CashShift\Infrastructure\Persistence\Repositories;

use App\CashShift\Domain\Entity\CashShift;
use App\CashShift\Domain\Interfaces\CashShiftRepositoryInterface;
use App\Shared\Domain\Exception\ConcurrencyException;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\CashShift\Infrastructure\Persistence\Models\EloquentCashShift;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
class EloquentCashShiftRepository implements CashShiftRepositoryInterface
{
    public function __construct(
        private EloquentCashShift $model,
        private EloquentUser $userModel,
    ) {}

    public function save(CashShift $cashShift): void
    {
        $openedByUserId = $this->userModel->newQuery()->where('uuid', $cashShift->openedByUserId()->getValue())->firstOrFail()->id;

        $this->model->newQuery()->create([
            'uuid' => $cashShift->uuid()->getValue(),
            'restaurant_id' => $cashShift->restaurantId(),
            'opened_by_user_id' => $openedByUserId,
            'closed_by_user_id' => null,
            'status' => $cashShift->status()->value,
            'opening_cash' => $cashShift->openingCash()->getValue(),
            'cash_total' => $cashShift->cashTotal()->getValue(),
            'card_total' => $cashShift->cardTotal()->getValue(),
            'bizum_total' => $cashShift->bizumTotal()->getValue(),
            'refund_total' => $cashShift->refundTotal()->getValue(),
            'counted_cash' => $cashShift->countedCash()?->getValue(),
            'cash_difference' => $cashShift->cashDifference()->getValue(),
            'notes' => $cashShift->notes(),
            'opened_at' => $cashShift->openedAt()->format('Y-m-d H:i:s'),
            'closed_at' => null,
        ]);
    }

    public function update(CashShift $cashShift): void
    {
        $closedByUserId = $cashShift->closedByUserId()
            ? $this->userModel->newQuery()->where('uuid', $cashShift->closedByUserId()->getValue())->firstOrFail()->id
            : null;

        $updatedRows = $this->model->newQuery()
            ->where('uuid', $cashShift->uuid()->getValue())
            ->when(
                $cashShift->persistedAt() !== null,
                fn ($query) => $query->where('updated_at', $cashShift->persistedAt()?->format('Y-m-d H:i:s')),
            )
            ->update([
                'closed_by_user_id' => $closedByUserId,
                'status' => $cashShift->status()->value,
                'cash_total' => $cashShift->cashTotal()->getValue(),
                'card_total' => $cashShift->cardTotal()->getValue(),
                'bizum_total' => $cashShift->bizumTotal()->getValue(),
                'refund_total' => $cashShift->refundTotal()->getValue(),
                'counted_cash' => $cashShift->countedCash()?->getValue(),
                'cash_difference' => $cashShift->cashDifference()->getValue(),
                'notes' => $cashShift->notes(),
                'closed_at' => $cashShift->closedAt()?->format('Y-m-d H:i:s'),
            ]);

        if ($updatedRows === 0) {
            throw ConcurrencyException::forResource('Cash shift', $cashShift->uuid()->getValue());
        }

        $freshUpdatedAt = $this->model->newQuery()
            ->where('uuid', $cashShift->uuid()->getValue())
            ->value('updated_at');

        if ($freshUpdatedAt !== null) {
            $cashShift->syncPersistedAt(DomainDateTime::create(new \DateTimeImmutable($freshUpdatedAt)));
        }
    }

    public function findOpenByRestaurant(int $restaurantId): ?CashShift
    {
        $model = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(int $restaurantId, string $uuid): ?CashShift
    {
        $model = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->where('uuid', $uuid)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(EloquentCashShift $model): CashShift
    {
        $openedByUuid = $this->userModel->newQuery()->find($model->opened_by_user_id)->uuid;
        $closedByUuid = $model->closed_by_user_id
            ? $this->userModel->newQuery()->find($model->closed_by_user_id)->uuid
            : null;

        return CashShift::fromPersistence(
            $model->uuid,
            $model->restaurant_id,
            $openedByUuid,
            $closedByUuid,
            $model->status,
            (int) $model->opening_cash,
            (int) $model->cash_total,
            (int) $model->card_total,
            (int) $model->bizum_total,
            (int) $model->refund_total,
            $model->counted_cash !== null ? (int) $model->counted_cash : null,
            (int) $model->cash_difference,
            $model->notes,
            $this->toDateTimeImmutable($model->opened_at),
            $this->toDateTimeImmutable($model->closed_at),
            $this->toDateTimeImmutable($model->updated_at),
        );
    }

    private function toDateTimeImmutable(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        return new \DateTimeImmutable((string) $value);
    }
}