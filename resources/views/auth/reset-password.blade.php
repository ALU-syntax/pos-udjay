@extends('layouts.auth-page')

@section('title', 'Atur Ulang Password')

@section('content')
    <h1 class="modal-title">Atur ulang password</h1>
    <p class="modal-desc">Buat password baru untuk akun Anda.</p>

    {{-- Link tidak valid / kedaluwarsa dilaporkan lewat error "email" --}}
    @error('email')
        <div class="auth-alert auth-alert--error" role="alert">
            {{ $message }}
            <a href="{{ route('password.request') }}">Minta link baru</a>
        </div>
    @enderror

    <form method="POST" action="{{ route('password.store') }}" data-auth-form novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="input-block">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
                autocomplete="username" readonly required>
        </div>

        <div class="input-block @error('password') is-invalid @enderror">
            <label for="password" class="form-label">Password Baru</label>
            <div class="password-field">
                <input id="password" name="password" type="password" autocomplete="new-password"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror required autofocus>
                @include('auth.partials.password-toggle', ['target' => 'password'])
            </div>
        </div>
        @error('password')
            <p class="auth-error" id="password-error">{{ $message }}</p>
        @else
            <p class="auth-hint">Minimal 8 karakter.</p>
        @enderror

        <div class="input-block">
            <label for="password_confirmation" class="form-label">Ulangi Password Baru</label>
            <div class="password-field">
                <input id="password_confirmation" name="password_confirmation" type="password"
                    autocomplete="new-password" required>
                @include('auth.partials.password-toggle', ['target' => 'password_confirmation'])
            </div>
        </div>

        <button type="submit" class="input-button auth-submit" data-loading-text="Menyimpan...">Simpan Password Baru</button>
    </form>

    <p class="auth-links"><a href="{{ route('login') }}">Kembali ke halaman login</a></p>
@endsection
