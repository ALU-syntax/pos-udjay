@extends('layouts.auth-page')

@section('title', 'Lupa Password')

@section('content')
    <h1 class="modal-title">Lupa password?</h1>
    <p class="modal-desc">
        Masukkan email akun Anda. Kami akan mengirim link untuk mengatur ulang password.
    </p>

    @if (session('status'))
        <div class="auth-alert auth-alert--success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" data-auth-form novalidate>
        @csrf

        <div class="input-block @error('email') is-invalid @enderror">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror required autofocus>
        </div>
        @error('email')
            <p class="auth-error" id="email-error">{{ $message }}</p>
        @enderror

        <button type="submit" class="input-button auth-submit" data-loading-text="Mengirim...">Kirim Link Reset</button>
    </form>

    <p class="auth-links"><a href="{{ route('login') }}">Kembali ke halaman login</a></p>
@endsection
