@php
    $editing = $user->exists;
    $selectedRole = (string) old('role', $user->role);
    $selectedStatus = (string) old('status', $user->status);
    $selectedOutlets = array_map('strval', (array) old('outlet_id', $user->outletIds()));
    // Role Kasir hanya boleh memegang satu outlet, jadi form memakai select tunggal
    $isKasirRole = fn ($role) => strtolower($role->name) === 'kasir';
    $kasirSelected = $roles->contains(fn ($role) => (string) $role->id === $selectedRole && $isKasirRole($role));
@endphp

@include('layouts.reports.partials.ui-styles')

<div class="main-content ob-page">
    {{-- Header --}}
    <div class="ob-header">
        <div>
            <div class="ob-header__eyebrow">Employee · Users</div>
            <h1 class="ob-header__title">
                <span class="ob-header__icon" aria-hidden="true"><i class="fas {{ $editing ? 'fa-user-edit' : 'fa-user-plus' }}"></i></span>
                {{ $editing ? 'Edit User' : 'Tambah User' }}
            </h1>
            <p class="ob-header__desc">
                {{ $editing ? 'Perbarui data akun, role, dan outlet milik '.$user->name.'.' : 'Buat akun baru untuk back office atau kasir.' }}
            </p>
        </div>
        <a href="{{ route('employee/user') }}" class="btn ob-btn ob-btn-ghost">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
        </a>
    </div>

    <div class="ob-card">
        {{-- ob-modal dipakai untuk gaya field (label, input, heading) yang sama dengan form modal report --}}
        <form action="{{ $editing ? route('employee/user/update', $user->id) : route('employee/user/store') }}" method="POST"
            class="ob-modal ob-user-form" novalidate>
            @csrf

            @if ($errors->any())
                <div class="ob-notice" role="alert">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <span>Data belum tersimpan. Periksa kembali isian yang ditandai merah.</span>
                </div>
            @endif

            <div class="row">
                {{-- Akun --}}
                <div class="col-12 ob-form-heading ob-form-heading--first">
                    <h3 class="ob-section-title"><i class="fas fa-id-card" aria-hidden="true"></i> Akun</h3>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                    <input id="name" name="name" type="text" maxlength="255" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" placeholder="Nama lengkap" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input id="username" name="username" type="text" maxlength="255" autocomplete="off"
                        class="form-control @error('username') is-invalid @enderror"
                        value="{{ old('username', $user->username) }}" placeholder="Dipakai untuk login" required>
                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" maxlength="255" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" placeholder="nama@contoh.com">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Opsional.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password @unless ($editing)<span class="text-danger">*</span>@endunless</label>
                    <div class="input-group has-validation">
                        <input id="password" name="password" type="password" autocomplete="new-password"
                            class="form-control ob-input-suffixed @error('password') is-invalid @enderror" @unless ($editing) required @endunless>
                        <button type="button" class="input-group-text ob-input-suffix ob-password-toggle"
                            aria-label="Tampilkan password" aria-controls="password"><i class="fas fa-eye" aria-hidden="true"></i></button>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @if ($editing)
                        <small class="text-muted">Kosongkan jika password tidak diubah.</small>
                    @endif
                </div>

                {{-- Akses --}}
                <div class="col-12 ob-form-heading">
                    <h3 class="ob-section-title"><i class="fas fa-user-shield" aria-hidden="true"></i> Akses</h3>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                    <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">Pilih role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" data-kasir="{{ $isKasirRole($role) ? 1 : 0 }}"
                                @selected((string) $role->id === $selectedRole)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="1" @selected($selectedStatus === '1')>Aktif</option>
                        <option value="0" @selected($selectedStatus === '0')>Nonaktif</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">User nonaktif tidak dapat login.</small>
                </div>

                {{-- Hanya satu select outlet yang aktif; yang tersembunyi di-disable agar tidak ikut terkirim --}}
                <div class="col-md-6 mb-3 {{ $kasirSelected ? 'd-none' : '' }}" data-outlet-field="multiple">
                    <label for="outlet_id_multiple" class="form-label">Outlet <span class="text-danger">*</span></label>
                    <select id="outlet_id_multiple" name="outlet_id[]" class="form-select ob-outlet-select @error('outlet_id') is-invalid @enderror"
                        multiple @disabled($kasirSelected)>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(in_array((string) $outlet->id, $selectedOutlets, true))>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                    @error('outlet_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Bisa memilih lebih dari satu outlet.</small>
                </div>
                <div class="col-md-6 mb-3 {{ $kasirSelected ? '' : 'd-none' }}" data-outlet-field="single">
                    <label for="outlet_id_single" class="form-label">Outlet <span class="text-danger">*</span></label>
                    <select id="outlet_id_single" name="outlet_id[]" class="form-select ob-outlet-select @error('outlet_id') is-invalid @enderror"
                        @disabled(!$kasirSelected)>
                        <option value="">Pilih outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(($selectedOutlets[0] ?? null) === (string) $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                    @error('outlet_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Kasir hanya terhubung ke satu outlet.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="pin" class="form-label">PIN Kasir</label>
                    <input id="pin" name="pin" type="password" inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="off"
                        class="form-control @error('pin') is-invalid @enderror" placeholder="6 digit angka">
                    @error('pin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">
                        {{ $editing ? 'Kosongkan jika PIN tidak diubah.' : 'Opsional. Dipakai untuk login di aplikasi kasir.' }}
                    </small>
                </div>
            </div>

            <div class="ob-form-actions">
                <a href="{{ route('employee/user') }}" class="btn ob-btn ob-btn-ghost">Batal</a>
                <button type="submit" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-save" aria-hidden="true"></i> {{ $editing ? 'Simpan Perubahan' : 'Simpan User' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('js')
    <script>
        $(function () {
            const form = $('.ob-user-form');

            form.find('.ob-outlet-select').select2({ width: '100%', placeholder: 'Pilih outlet' });

            // Kasir memakai select outlet tunggal, role lain boleh banyak outlet
            form.on('change', '#role', function () {
                const kasir = $(this).find('option:selected').data('kasir') === 1;
                form.find('[data-outlet-field="multiple"]').toggleClass('d-none', kasir)
                    .find('select').prop('disabled', kasir);
                form.find('[data-outlet-field="single"]').toggleClass('d-none', !kasir)
                    .find('select').prop('disabled', !kasir);
            });

            form.on('click', '.ob-password-toggle', function () {
                const input = form.find('#password');
                const show = input.attr('type') === 'password';
                input.attr('type', show ? 'text' : 'password');
                $(this).attr('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password')
                    .find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
            });

            form.on('input', '#pin', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
            });
        });
    </script>
@endpush

@push('css')
    <style>
        .ob-user-form {
            padding: 1.25rem 1.5rem;
        }

        .ob-user-form .ob-notice {
            margin-bottom: 1.25rem;
        }

        .ob-user-form .ob-password-toggle {
            cursor: pointer;
        }

        /* invalid-feedback bootstrap hanya tampil jika bersebelahan dengan input; select2 menyisipkan elemen di antaranya */
        .ob-user-form .is-invalid ~ .invalid-feedback {
            display: block;
        }

        .ob-user-form .is-invalid + .select2-container .select2-selection {
            border-color: var(--ob-danger) !important;
        }

        .ob-page .select2-container--default .select2-selection--multiple {
            min-height: 42px;
            border: 1px solid var(--ob-border-strong);
            border-radius: var(--ob-radius-sm);
        }

        .ob-page .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--ob-primary);
            box-shadow: 0 0 0 3px rgba(21, 114, 232, .15);
        }

        .ob-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            margin-top: .5rem;
            padding-top: 1.1rem;
            border-top: 1px solid var(--ob-border);
        }
    </style>
@endpush
