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
     * @param  \Closure(\Illuminate\Http\Request): 
     * @param  string  ')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
       
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }

        $user = Auth::user();

        $userRole = strtoupper($user->role);

    
        if (!in_array($userRole, $roles, true)) {
        
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk membuka halaman ini sebagai ' . $userRole . '.');
        }

        
        return $next($request);
    }
}