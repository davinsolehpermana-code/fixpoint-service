<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'cancellation_reason',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'schedule_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'booking_id', 'booking_id');
    }

    public function serviceRecord(): HasOne
    {
        return $this->hasOne(ServiceRecord::class, 'booking_id', 'booking_id');
    }
}
