<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('email')
            ->orderBy('user_id')
            ->paginate(15);

        return view('crud.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('crud.users.form', ['user' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['ADMIN', 'PEMILIK_USAHA'])],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('crud.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['ADMIN', 'PEMILIK_USAHA'])],
        ]);

        if (
            (string) $user->user_id === (string) $request->user()->getAuthIdentifier()
            && $validated['role'] !== $user->role
        ) {
            return back()->with('error', 'Role akun yang sedang digunakan tidak dapat diubah.');
        }

        if (
            $user->role === 'ADMIN'
            && $validated['role'] !== 'ADMIN'
            && ! User::query()->where('role', 'ADMIN')->where('user_id', '!=', $user->user_id)->exists()
        ) {
            return back()->with('error', 'Setidaknya harus ada satu akun ADMIN.');
        }

        if (blank($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ((string) $user->user_id === (string) $request->user()->getAuthIdentifier()) {
            return back()->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        if (
            $user->role === 'ADMIN'
            && ! User::query()->where('role', 'ADMIN')->where('user_id', '!=', $user->user_id)->exists()
        ) {
            return back()->with('error', 'Setidaknya harus ada satu akun ADMIN.');
        }

        if (
            Payment::query()->where('processed_by', $user->user_id)->exists()
            || Booking::query()->where('handled_by', $user->user_id)->exists()
            || DB::table('service_records')->where('handled_by', $user->user_id)->exists()
        ) {
            return back()->with('error', 'Pengguna memiliki catatan transaksi atau servis dan tidak dapat dihapus.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
