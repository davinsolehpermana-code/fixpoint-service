<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'email' => 'admin@fixpoint.test',
            'password' => Hash::make('Admin12345'),
            'role' => 'ADMIN',
        ]);

        User::create([
            'email' => 'pemilik@fixpoint.test',
            'password' => Hash::make('Pemilik12345'),
            'role' => 'PEMILIK_USAHA',
        ]);
    }
}