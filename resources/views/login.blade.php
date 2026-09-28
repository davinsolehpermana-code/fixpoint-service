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
        border-radius: 14px;
        padding: 30px;
        box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
    }

    .login-header {
        text-align: center;
        margin-bottom: 24px;
    }

    .login-label {
        color: #2563eb;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 7px;
    }

    .login-title {
        color: #111827;
        font-size: 28px;
        margin-bottom: 7px;
    }

    .login-description {
        color: #6b7280;
        font-size: 13px;
    }

    .form-group {
        margin-bottom: 17px;
    }

    .form-label {
        display: block;
        margin-bottom: 7px;
        color: #374151;
        font-size: 13px;
        font-weight: 700;
    }

    .form-control {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 11px 13px;
        font-size: 14px;
        outline: none;
        background: #ffffff;
    }

    .form-control:focus {
        border-color: #2563eb;
    }

    .role-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
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
        border: 1px solid #dbe2ea;
        border-radius: 9px;
        padding: 13px;
        cursor: pointer;
        text-align: center;
        color: #4b5563;
        font-size: 13px;
        font-weight: 700;
        transition: 0.2s;
    }

    .role-option label:hover {
        border-color: #2563eb;
        background: #eff6ff;
    }

    .role-option input:checked + label {
        border-color: #2563eb;
        background: #eff6ff;
        color: #2563eb;
    }

    .login-button {
        width: 100%;
        border: none;
        background: #2563eb;
        color: #ffffff;
        padding: 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        margin-top: 6px;
    }

    .login-button:hover {
        background: #1d4ed8;
    }

    .login-note {
        margin-top: 18px;
        color: #9ca3af;
        font-size: 11px;
        text-align: center;
    }

    @media (max-width: 480px) {
        .login-card {
            padding: 22px;
        }

        .role-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="login-page">

    <div class="login-card">

        <div class="login-header">

            <div class="login-label">
                FIXPOINT SERVICE
            </div>

            <h1 class="login-title">
                Masuk
            </h1>

            <p class="login-description">
                Masuk ke panel pengelolaan FixPoint Service.
            </p>

        </div>

        <form>

            <div class="form-group">

                <label class="form-label">
                    Masuk Sebagai
                </label>

                <div class="role-grid">

                    <div class="role-option">
                        <input
                            type="radio"
                            id="admin"
                            name="role"
                            value="admin"
                            checked
                        >

                        <label for="admin">
                            Admin
                        </label>
                    </div>

                    <div class="role-option">
                        <input
                            type="radio"
                            id="pemilik"
                            name="role"
                            value="pemilik"
                        >

                        <label for="pemilik">
                            Pemilik Usaha
                        </label>
                    </div>

                </div>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Email
                </label>

                <input
                    type="email"
                    class="form-control"
                    placeholder="Masukkan email"
                >

            </div>

            <div class="form-group">

                <label class="form-label">
                    Password
                </label>

                <input
                    type="password"
                    class="form-control"
                    placeholder="Masukkan password"
                >

            </div>

            <button type="button" class="login-button">
                Masuk
            </button>

        </form>

        <div class="login-note">
            Akses ini digunakan oleh Admin dan Pemilik Usaha.
        </div>

    </div>

</div>

@endsection