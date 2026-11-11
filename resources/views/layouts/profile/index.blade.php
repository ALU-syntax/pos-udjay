@extends('layouts.app')

@section('content')
    @php
        $statusLabel = \App\Models\User::STATUS[(string) $user->status] ?? '—';
        $isActive = (string) $user->status === '1';
        // Form password dan PIN memakai error bag sendiri agar pesannya tidak muncul di form lain
        $passwordErrors = $errors->updatePassword;
        $pinErrors = $errors->updatePin;
        $hasPin = filled($user->pin);
    @endphp

    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Akun</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-user-circle"></i></span>
                    Profil Saya
                </h1>
                <p class="ob-header__desc">Perbarui data akun, password, dan PIN kasir milik Anda sendiri.</p>
            </div>
        </div>

        {{-- Jarak vertikal antar kartu sudah diatur margin .ob-card, jadi cukup gutter horizontal --}}
        <div class="row gx-3">
            {{-- Informasi profil --}}
            <div class="col-xl-7">
                <div class="ob-card">
                    {{-- ob-modal dipakai untuk gaya field (label, input, heading) yang sama dengan form modal report --}}
                    <form action="{{ route('profile.update') }}" method="POST" class="ob-modal ob-profile-form" novalidate>
                        @csrf
                        @method('PATCH')

                        @if ($errors->any())
                            <div class="ob-notice" role="alert">
                                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                <span>Profil belum tersimpan. Periksa kembali isian yang ditandai merah.</span>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-12 ob-form-heading ob-form-heading--first">
                                <h3 class="ob-section-title"><i class="fas fa-id-card" aria-hidden="true"></i> Informasi Profil</h3>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                                <input id="name" name="name" type="text" maxlength="255" autocomplete="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}" placeholder="Nama lengkap" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input id="username" name="username" type="text" maxlength="255" autocomplete="username"
                                    class="form-control @error('username') is-invalid @enderror"
                                    value="{{ old('username', $user->username) }}" required>
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Dipakai untuk login di aplikasi kasir.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input id="email" name="email" type="email" maxlength="255" autocomplete="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}" placeholder="nama@contoh.com" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Dipakai untuk login back office.</small>
                            </div>

                            <div class="col-12 ob-form-heading">
                                <h3 class="ob-section-title"><i class="fas fa-user-shield" aria-hidden="true"></i> Akses</h3>
                            </div>
                            <div class="col-12">
                                <dl class="ob-meta">
                                    <div class="ob-meta__item">
                                        <dt>Role</dt>
                                        <dd><span>{{ $roleName }}</span></dd>
                                    </div>
                                    <div class="ob-meta__item">
                                        <dt>Status</dt>
                                        <dd>
                                            <span class="ob-profile-status {{ $isActive ? 'ob-profile-status--active' : '' }}">{{ $statusLabel }}</span>
                                        </dd>
                                    </div>
                                    <div class="ob-meta__item ob-meta__item--wide">
                                        <dt>Outlet</dt>
                                        <dd>
                                            @forelse ($outletNames as $outletName)
                                                <span class="ob-chip">{{ $outletName }}</span>
                                            @empty
                                                <span></span>
                                            @endforelse
                                        </dd>
                                    </div>
                                </dl>
                                <small class="text-muted d-block ob-profile-note">
                                    Role, status, dan outlet hanya bisa diubah admin lewat menu Employee.
                                </small>
                            </div>
                        </div>

                        <div class="ob-form-actions">
                            <button type="submit" class="btn ob-btn ob-btn-primary">
                                <i class="fas fa-save" aria-hidden="true"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Ganti password --}}
            <div class="col-xl-5">
                <div class="ob-card">
                    <form action="{{ route('password.update') }}" method="POST" class="ob-modal ob-profile-form" id="password-form" novalidate>
                        @csrf
                        @method('PUT')

                        @if ($passwordErrors->any())
                            <div class="ob-notice" role="alert">
                                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                <span>Password belum diganti. Periksa kembali isian yang ditandai merah.</span>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-12 ob-form-heading ob-form-heading--first">
                                <h3 class="ob-section-title"><i class="fas fa-key" aria-hidden="true"></i> Ganti Password</h3>
                            </div>

                            @foreach ([
                                ['current_password', 'Password Saat Ini', 'current-password', null],
                                ['password', 'Password Baru', 'new-password', 'Minimal 8 karakter.'],
                                ['password_confirmation', 'Ulangi Password Baru', 'new-password', null],
                            ] as [$field, $label, $autocomplete, $hint])
                                <div class="col-12 mb-3">
                                    <label for="{{ $field }}" class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                                    <div class="input-group has-validation">
                                        <input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="{{ $autocomplete }}"
                                            class="form-control ob-input-suffixed {{ $passwordErrors->has($field) ? 'is-invalid' : '' }}" required>
                                        <button type="button" class="input-group-text ob-input-suffix ob-password-toggle"
                                            aria-label="Tampilkan password" aria-pressed="false" aria-controls="{{ $field }}">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </button>
                                        @if ($passwordErrors->has($field))
                                            <div class="invalid-feedback">{{ $passwordErrors->first($field) }}</div>
                                        @endif
                                    </div>
                                    @if ($hint)
                                        <small class="text-muted">{{ $hint }}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="ob-form-actions">
                            <button type="submit" class="btn ob-btn ob-btn-primary">
                                <i class="fas fa-key" aria-hidden="true"></i> Ganti Password
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Ganti PIN kasir --}}
                <div class="ob-card">
                    <form action="{{ route('profile.pin') }}" method="POST" class="ob-modal ob-profile-form" id="pin-form" novalidate>
                        @csrf
                        @method('PUT')

                        @if ($pinErrors->any())
                            <div class="ob-notice" role="alert">
                                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                <span>PIN belum diganti. Periksa kembali isian yang ditandai merah.</span>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-12 ob-form-heading ob-form-heading--first">
                                <h3 class="ob-section-title"><i class="fas fa-th" aria-hidden="true"></i> {{ $hasPin ? 'Ganti PIN Kasir' : 'Atur PIN Kasir' }}</h3>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block ob-profile-note">
                                    PIN 6 digit dipakai untuk login di aplikasi kasir.
                                    {{ $hasPin ? 'PIN lama langsung tidak berlaku setelah diganti.' : 'Akun ini belum memiliki PIN.' }}
                                </small>
                            </div>

                            {{-- [name, id, label, jenis, autocomplete, hint] --}}
                            @foreach ([
                                ['current_password', 'pin_current_password', 'Password Saat Ini', 'password', 'current-password', 'Untuk memastikan ini memang Anda.'],
                                ['pin', 'pin', 'PIN Baru', 'pin', 'off', '6 digit angka.'],
                                ['pin_confirmation', 'pin_confirmation', 'Ulangi PIN Baru', 'pin', 'off', null],
                            ] as [$field, $id, $label, $kind, $autocomplete, $hint])
                                <div class="col-12 mb-3">
                                    <label for="{{ $id }}" class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                                    <div class="input-group has-validation">
                                        <input id="{{ $id }}" name="{{ $field }}" type="password" autocomplete="{{ $autocomplete }}"
                                            @if ($kind === 'pin') inputmode="numeric" pattern="\d{6}" maxlength="6" data-pin-input @endif
                                            class="form-control ob-input-suffixed {{ $pinErrors->has($field) ? 'is-invalid' : '' }}" required>
                                        <button type="button" class="input-group-text ob-input-suffix ob-password-toggle"
                                            aria-label="Tampilkan {{ $kind === 'pin' ? 'PIN' : 'password' }}" aria-pressed="false"
                                            aria-controls="{{ $id }}" data-label="{{ $kind === 'pin' ? 'PIN' : 'password' }}">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </button>
                                        @if ($pinErrors->has($field))
                                            <div class="invalid-feedback">{{ $pinErrors->first($field) }}</div>
                                        @endif
                                    </div>
                                    @if ($hint)
                                        <small class="text-muted">{{ $hint }}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="ob-form-actions">
                            <button type="submit" class="btn ob-btn ob-btn-primary">
                                <i class="fas fa-th" aria-hidden="true"></i> {{ $hasPin ? 'Ganti PIN' : 'Simpan PIN' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(function () {
            const success = @json(session('success'));
            if (success) {
                showToast('success', success);
            }

            $('.ob-profile-form').on('click', '.ob-password-toggle', function () {
                const input = $('#' + $(this).attr('aria-controls'));
                const show = input.attr('type') === 'password';
                const label = $(this).data('label') || 'password';

                input.attr('type', show ? 'text' : 'password');
                $(this).attr('aria-label', (show ? 'Sembunyikan ' : 'Tampilkan ') + label)
                    .attr('aria-pressed', show ? 'true' : 'false')
                    .find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
            });

            // PIN hanya boleh angka, maksimal 6 digit
            $('.ob-profile-form').on('input', '[data-pin-input]', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
            });

            // Setelah gagal ganti password/PIN, bawa form-nya ke layar (di HP posisinya di bawah form profil)
            const failedForm = @json($passwordErrors->any() ? 'password-form' : ($pinErrors->any() ? 'pin-form' : null));
            if (failedForm) {
                document.getElementById(failedForm).scrollIntoView({ block: 'center' });
            }
        });
    </script>
@endpush

@push('css')
    <style>
        .ob-profile-form {
            padding: 1.25rem 1.5rem;
        }

        .ob-profile-form .ob-notice {
            margin-bottom: 1.25rem;
        }

        .ob-profile-form .ob-password-toggle {
            cursor: pointer;
        }

        .ob-profile-form .ob-meta {
            margin-bottom: .6rem;
        }

        .ob-profile-form .ob-meta dd {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .ob-profile-note {
            margin-bottom: 1rem;
        }

        .ob-profile-status {
            color: var(--ob-muted);
        }

        .ob-profile-status--active {
            color: var(--ob-success);
        }

        .ob-profile-form .ob-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            margin-top: .5rem;
            padding-top: 1.1rem;
            border-top: 1px solid var(--ob-border);
        }

        @media (max-width: 575.98px) {
            .ob-profile-form {
                padding: 1rem;
            }

            .ob-profile-form .ob-form-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush
