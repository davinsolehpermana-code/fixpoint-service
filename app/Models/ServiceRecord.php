<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRecord extends Model
{
    use HasFactory;

    protected $table = 'service_records';

    protected $primaryKey = 'service_record_id';

    protected $fillable = [
        'booking_id',
        'handled_by',
        'diagnosis',
        'estimated_cost',
        'final_cost',
        'service_status',
        'service_notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by', 'user_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'service_record_id', 'service_record_id');
    }
}
