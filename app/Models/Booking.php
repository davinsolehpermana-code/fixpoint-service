<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'service_id_code',
        'customer_id',
        'device_id',
        'service_id',
        'schedule_id',
        'handled_by',
        'booking_date',
        'status',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi Customer
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(
            Customer::class,
            'customer_id',
            'customer_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi Device
    |--------------------------------------------------------------------------
    */

    public function device()
    {
        return $this->belongsTo(
            Device::class,
            'device_id',
            'device_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi Service
    |--------------------------------------------------------------------------
    */

    public function service()
    {
        return $this->belongsTo(
            Service::class,
            'service_id',
            'service_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi Schedule
    |--------------------------------------------------------------------------
    */

    public function schedule()
    {
        return $this->belongsTo(
            Schedule::class,
            'schedule_id',
            'schedule_id'
        );
    }
}