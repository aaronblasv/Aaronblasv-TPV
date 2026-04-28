<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentServiceWindowLine extends Model
{
    use SoftDeletes;

    protected $table = 'order_service_window_lines';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'order_service_window_id',
        'order_line_id',
        'product_name',
        'quantity',
        'price',
        'tax_percentage',
        'discount_type',
        'discount_value',
        'discount_amount',
        'line_subtotal',
        'tax_amount',
        'line_total',
    ];

    public function serviceWindow(): BelongsTo
    {
        return $this->belongsTo(EloquentServiceWindow::class, 'order_service_window_id');
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(EloquentOrderLine::class, 'order_line_id');
    }
}
