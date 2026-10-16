<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // Sesuaikan dengan nama tabel di database
    protected $table = 'payments';

    // Primary key khusus sesuai ERD
    protected $primaryKey = 'payment_id';

    protected $fillable = [
    'booking_id',
    'customer_id',
    'service_record_id',
    'processed_by',
    'amount',
    'payment_method',
    'payment_status',
    'payment_proof',
    'payment_date',
     ];

    // Casting tipe data
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    // Relasi ke Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    // Relasi ke ServiceRecord (jika diperlukan nanti)
    public function serviceRecord()
    {
        return $this->belongsTo(ServiceRecord::class, 'service_record_id', 'service_record_id');
    }

    // Relasi ke User (processed_by)
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by', 'user_id');
    }
}