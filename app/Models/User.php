<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Jika primary key di database kamu 'user_id' (sesuai ERD), biarkan baris ini. 
    // Jika 'id' (bawaan Laravel), hapus baris ini.
    protected $primaryKey = 'user_id'; 

    protected $fillable = [
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}