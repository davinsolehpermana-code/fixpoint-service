<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $primaryKey = 'device_id';

    protected $fillable = [
        'customer_id',
        'device_type',
        'brand',
        'model',
        'complaint',
    ];
}