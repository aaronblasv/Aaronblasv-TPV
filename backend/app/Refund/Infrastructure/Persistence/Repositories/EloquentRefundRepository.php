<?php

declare(strict_types=1);

namespace App\Refund\Infrastructure\Persistence\Repositories;

use App\Refund\Domain\Entity\Refund;
use App\Refund\Domain\Entity\RefundLine;
use App\Refund\Domain\Exception\RefundNotFoundException;
use App\Refund\Domain\Interfaces\RefundRepositoryInterface;
use App\Refund\Infrastructure\Persistence\Models\EloquentRefund;
use App\Refund\Infrastructure\Persistence\Models\EloquentRefundLine;
use App\Sale\Domain\Exception\SaleLineNotFoundException;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentRefundRepository implements RefundRepositoryInterface
{
    public function __construct(
        private EloquentRefund $model,
        private EloquentRefundLine $lineModel,
        private EloquentSale $saleModel,
        private EloquentSaleLine $saleLineModel,
        private EloquentUser $userModel,
    ) {}

    public function save(Refund $refund): void
    {
        $saleId = $this->resolveSaleId($refund->saleId()->getValue());
        $userId = $this->resolveUserId($refund->userId()->getValue());

        $this->model->newQuery()->create([
            'uuid' => $refund->uuid()->getValue(),
            'restaurant_id' => $refund->restaurantId(),
            'sale_id' => $saleId,
            'user_id' => $userId,
            'type' => $refund->type(),
            'method' => $refund->method(),
            'reason' => $refund->reason(),
            'subtotal' => $refund->subtotal(),
            'tax_amount' => $refund->taxAmount(),
            'total' => $refund->total(),
        ]);
    }

    public function saveLine(RefundLine $line): void
    {
        $refundId = $this->resolveRefundId($line->refundId()->getValue());
        $saleLineId = $this->resolveSaleLineId($line->saleLineId()->getValue());

        $this->lineModel->newQuery()->create([
            'uuid' => $line->uuid()->getValue(),
            'refund_id' => $refundId,
            'sale_line_id' => $saleLineId,
            'quantity' => $line->quantity(),
            'subtotal' => $line->subtotal(),
            'tax_amount' => $line->taxAmount(),
            'total' => $line->total(),
        ]);
    }

    private function resolveSaleId(string $saleUuid): int
    {
        try {
            return $this->saleModel->newQuery()->where('uuid', $saleUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new SaleNotFoundException($saleUuid);
        }
    }

    private function resolveUserId(string $userUuid): int
    {
        try {
            return $this->userModel->newQuery()->where('uuid', $userUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new UserNotFoundException($userUuid);
        }
    }

    private function resolveRefundId(string $refundUuid): int
    {
        try {
            return $this->model->newQuery()->where('uuid', $refundUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new RefundNotFoundException($refundUuid);
        }
    }

    private function resolveSaleLineId(string $saleLineUuid): int
    {
        try {
            return $this->saleLineModel->newQuery()->where('uuid', $saleLineUuid)->firstOrFail()->id;
        } catch (ModelNotFoundException) {
            throw new SaleLineNotFoundException($saleLineUuid);
        }
    }
}
