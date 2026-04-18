<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class EloquentRestaurant extends Model
{

    use SoftDeletes;

    protected $table = 'restaurants';

    protected $fillable = [
        'uuid',
        'name',
        'legal_name',
        'tax_id',
        'email',
        'password'
    ];

}