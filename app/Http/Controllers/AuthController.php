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
     * Proses login dan validasi akses berdasarkan role yang dipilih.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'role' => ['required', 'in:admin,pemilik'],
        ]);

        // Mapping nilai tombol login ke role yang tersimpan di database.
        $roleMap = [
            'admin' => 'ADMIN',
            'pemilik' => 'PEMILIK_USAHA',
        ];

        $selectedRole = $roleMap[$credentials['role']];

        // Login menggunakan email dan password.
        if (!Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->withInput($request->only('email', 'role'));
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Pastikan akun yang login memiliki role sesuai tombol yang dipilih.
        if (strtoupper((string) $user->role) !== $selectedRole) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $roleName = $credentials['role'] === 'admin'
                ? 'Admin'
                : 'Pemilik Usaha';

            return back()->withErrors([
                'role' => 'Akun ini tidak memiliki akses sebagai ' . $roleName . '. Silakan pilih role yang sesuai.',
            ])->withInput($request->only('email', 'role'));
        }

        // Redirect sesuai role.
        if ($user->role === 'ADMIN') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'PEMILIK_USAHA') {
            return redirect()->route('pemilik.dashboard');
        }

        // Pengaman jika ada role yang tidak dikenali.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Role akun tidak dikenali oleh sistem.',
        ]);
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