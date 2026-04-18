<?php

declare(strict_types=1);

namespace App\Zone\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class EloquentZone extends Model
{

    use SoftDeletes;

    protected $table = 'zones';

    protected $fillable = [
        'uuid',
        'name',
        'restaurant_id',
    ];

}