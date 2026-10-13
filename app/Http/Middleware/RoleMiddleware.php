<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  (bisa terima 1 atau lebih role, misal: 'ADMIN', 'PEMILIK_USAHA')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }

        $user = Auth::user();

        // 2. Normalisasi role user (jaga-jaga jika ada perbedaan case)
        $userRole = strtoupper($user->role);

        // 3. Cek apakah role user ada di daftar role yang diizinkan
        //    Contoh: route pakai 'role:ADMIN' → $roles = ['ADMIN']
        if (!in_array($userRole, $roles, true)) {
            // S2-01: Kontrol Akses - Tolak akses ilegal antar role
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk membuka halaman ini sebagai ' . $userRole . '.');
        }

        // 4. Lanjutkan request jika role valid
        return $next($request);
    }
}