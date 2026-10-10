<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $primaryKey = 'service_id';

    protected $fillable = [
        'name',
        'description',
        'estimated_price',
        'is_active',
    ];
}