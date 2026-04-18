<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class EloquentTable extends Model
{

    use SoftDeletes;

    protected $table = 'tables';

    protected $fillable = [
        'uuid',
        'name',
        'zone_id',
        'restaurant_id',
        'merged_with',
    ];

}