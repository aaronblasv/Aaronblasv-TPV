<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSaleLine extends Model
{
    use SoftDeletes;

    protected $table = 'sales_lines';

    protected $fillable = [
        'uuid',
        'restaurant_id',
        'sale_id',
        'order_line_id',
        'user_id',
        'quantity',
        'price',
        'tax_percentage',
        'line_subtotal',
        'tax_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'line_total',
        'refunded_quantity',
    ];
}