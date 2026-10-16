<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function showLogin()
    {
        return view('login.login');
    }

    /**
     * Proses login dengan validasi role dari form.
     * S2-01: Kontrol Akses Multi-Role - Pastikan role di DB cocok dengan yang dipilih.
     */
    public function login(Request $request)
    {
        // 1. Validasi input form (tambah validasi 'role' dari radio button)
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'role'     => 'required|in:admin,pemilik', // ← TAMBAHAN: Validasi role dari form
        ]);

        // 2. Coba autentikasi dengan email & password
        if (Auth::attempt([
            'email'    => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            $request->session()->regenerate();

            $user = Auth::user();
            $selectedRole = strtoupper($credentials['role']); // Normalisasi: 'admin' → 'ADMIN'

            // 3. Cek apakah role di database COCOK dengan yang dipilih di form
            if ($user->role === $selectedRole) {
                // 4. Redirect sesuai role (S2-01: Pemisahan hak akses)
                if ($user->role === 'ADMIN') {
                    return redirect()->route('admin.dashboard');
                } elseif ($user->role === 'PEMILIK_USAHA') {
                    return redirect()->route('pemilik.dashboard');
                }

                // Fallback jika role tidak dikenali
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login')->withErrors([
                    'email' => 'Role akun tidak dikenali oleh sistem.',
                ]);
            }

            // 5. Jika role TIDAK cocok → logout & tampilkan error
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Akun ini tidak memiliki akses sebagai ' . ucfirst($credentials['role']) . '. Silakan pilih role yang sesuai.',
            ])->onlyInput('email');
        }

        // 6. Jika email/password salah
        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    /**
     * Proses logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}