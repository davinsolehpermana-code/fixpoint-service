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

    protected $casts = [
        'estimated_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
