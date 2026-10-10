@extends('layouts.app')

@section('title', 'Login')

@section('content')

<style>
    .login-page {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-card {
        width: 100%;
        max-width: 420px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .login-header {
        text-align: center;
        margin-bottom: 28px;
    }

    .login-label {
        display: inline-block;
        font-size: 12px;
        font-weight: 700;
        color: #2563eb;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .login-title {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #111827;
    }

    .login-description {
        margin-top: 8px;
        font-size: 14px;
        color: #6b7280;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
    }

    .form-control {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;
        transition: 0.2s;
    }

    .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .password-wrapper {
        position: relative;
    }

    .password-wrapper .form-control {
        padding-right: 45px;
    }

    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        cursor: pointer;
        padding: 4px;
        color: #6b7280;
    }

    .password-toggle svg {
        width: 20px;
        height: 20px;
    }

    .role-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .role-option {
        position: relative;
    }

    .role-option input {
        position: absolute;
        opacity: 0;
    }

    .role-option label {
        display: block;
        padding: 12px;
        text-align: center;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        transition: 0.2s;
    }

    .role-option input:checked + label {
        border-color: #2563eb;
        background: #eff6ff;
        color: #2563eb;
    }

    .login-button {
        width: 100%;
        padding: 13px;
        border: none;
        border-radius: 10px;
        background: #2563eb;
        color: #ffffff;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
    }

    .login-button:hover {
        background: #1d4ed8;
    }

    .login-note {
        margin-top: 18px;
        text-align: center;
        font-size: 12px;
        color: #6b7280;
    }
</style>

<div class="login-page">
    <div class="login-card">

        <div class="login-header">
            <span class="login-label">FIXPOINT SERVICE</span>

            <h1 class="login-title">Masuk</h1>

            <p class="login-description">
                Silakan masuk untuk mengakses sistem.
            </p>
        </div>

        {{-- Form Login --}}
        <form method="POST" action="{{ route('login.process') }}">
            @csrf

            {{-- Role --}}
            <div class="form-group">
                <label class="form-label">Role</label>

                <div class="role-grid">

                    <div class="role-option">
                        <input
                            type="radio"
                            id="roleAdmin"
                            name="role"
                            value="admin"
                            checked
                        >

                        <label for="roleAdmin">
                            Admin
                        </label>
                    </div>

                    <div class="role-option">
                        <input
                            type="radio"
                            id="rolePemilik"
                            name="role"
                            value="pemilik"
                        >

                        <label for="rolePemilik">
                            Pemilik Usaha
                        </label>
                    </div>

                </div>
            </div>

            {{-- Email --}}
            <div class="form-group">
                <label for="email" class="form-label">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="Masukkan email"
                    value="{{ old('email') }}"
                    required
                >
            </div>

            {{-- Password --}}
            <div class="form-group">
                <label for="password" class="form-label">
                    Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                    >
                        {{-- Mata tertutup --}}
                        <svg
                            id="eyeClosed"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-7-9-7a16.75 16.75 0 013.375-4.125M6.228 6.228A9.956 9.956 0 0112 5c5 0 9 7 9 7a16.953 16.953 0 01-3.087 3.912M9.88 9.88a3 3 0 104.24 4.24M3 3l18 18"
                            />
                        </svg>

                        {{-- Mata terbuka --}}
                        <svg
                            id="eyeOpen"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            style="display: none;"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7C20.268 16.057 16.477 19 12 19c-4.477 0-8.268-2.943-9.542-7z"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15a3 3 0 100-6 3 3 0 000 6z"
                            />
                        </svg>
                    </button>

                </div>
            </div>

            {{-- Error --}}
            @if ($errors->any())
                <div style="
                    margin-bottom: 20px;
                    padding: 12px;
                    background: #fef2f2;
                    border: 1px solid #fecaca;
                    border-radius: 10px;
                    color: #b91c1c;
                    font-size: 14px;
                ">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Tombol Login --}}
            <button
                type="submit"
                class="login-button"
            >
                Masuk
            </button>

        </form>

        <div class="login-note">
            Akses ini digunakan oleh Admin dan Pemilik Usaha.
        </div>

    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeClosed = document.getElementById('eyeClosed');
    const eyeOpen = document.getElementById('eyeOpen');

    togglePassword.addEventListener('click', function () {
        const isPassword = password.type === 'password';

        password.type = isPassword ? 'text' : 'password';

        eyeClosed.style.display = isPassword ? 'none' : 'block';
        eyeOpen.style.display = isPassword ? 'block' : 'none';

        togglePassword.setAttribute(
            'aria-label',
            isPassword
                ? 'Sembunyikan password'
                : 'Tampilkan password'
        );
    });
</script>

@endsection