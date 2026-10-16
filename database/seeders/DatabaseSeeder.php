<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Service;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Isi Data Layanan (Aman dari error dobel)
        Service::firstOrCreate(
            ['name' => 'Servis Laptop'],
            [
                'description' => 'Pemeriksaan dan perbaikan laptop.',
                'estimated_price' => 50000,
                'is_active' => true
            ]
        );

        Service::firstOrCreate(
            ['name' => 'Servis PC Desktop'],
            [
                'description' => 'Pemeriksaan dan perbaikan PC desktop.',
                'estimated_price' => 75000,
                'is_active' => true
            ]
        );

        // 2. Isi Data User (Aman dari error dobel)
        User::firstOrCreate(
            ['email' => 'admin@fixpoint.test'],
            [
                'password' => Hash::make('Admin12345'),
                'role' => 'ADMIN'
            ]
        );

        User::firstOrCreate(
            ['email' => 'pemilik@fixpoint.test'],
            [
                'password' => Hash::make('Pemilik12345'),
                'role' => 'PEMILIK_USAHA'
            ]
        );
    }
}