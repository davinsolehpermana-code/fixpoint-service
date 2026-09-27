<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'disconnected',
        ], 500);
    }
});