<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
   })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Kesalahan database tetap dicatat ke log, tetapi pengguna hanya melihat
        // halaman 500 yang ramah tanpa detail query/stack trace saat debug mati.
        $exceptions->render(function (QueryException $exception, Request $request) {
            if (config('app.debug')) {
                return null;
            }

            report($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Permintaan belum dapat diproses. Silakan coba beberapa saat lagi.',
                ], 500);
            }

            return response()->view('errors.500', [], 500);
        });
    })->create();
