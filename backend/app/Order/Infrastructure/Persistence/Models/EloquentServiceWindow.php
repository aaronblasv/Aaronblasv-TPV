<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentServiceWindow extends Model
{
    use SoftDeletes;

    protected $table = 'order_service_windows';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'order_id',
        'sent_by_user_id',
        'sent_by_user_name',
        'window_number',
        'sent_at',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EloquentOrder::class, 'order_id');
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(\App\User\Infrastructure\Persistence\Models\EloquentUser::class, 'sent_by_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EloquentServiceWindowLine::class, 'order_service_window_id');
    }
}
