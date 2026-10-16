<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'schedule_date',
        'start_time',
        'end_time',
        'max_booking',
        'current_booking',
        'status',
    ];
}