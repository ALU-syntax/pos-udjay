@php
    $editing = $role->exists;
@endphp

@include('layouts.reports.partials.ui-styles')

<div class="main-content ob-page">
    {{-- Header --}}
    <div class="ob-header">
        <div>
            <div class="ob-header__eyebrow">Employee · Role</div>
            <h1 class="ob-header__title">
                <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-user-tag"></i></span>
                {{ $editing ? 'Edit Role' : 'Tambah Role' }}
            </h1>
            <p class="ob-header__desc">
                {{ $editing ? 'Ubah nama role '.$role->name.'. Hak akses role tidak ikut berubah.' : 'Buat role baru, lalu atur aksesnya di menu Hak Akses.' }}
            </p>
        </div>
        <a href="{{ route('employee/roles') }}" class="btn ob-btn ob-btn-ghost">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
        </a>
    </div>

    <div class="ob-card">
        {{-- ob-modal dipakai untuk gaya field (label, input, heading) yang sama dengan form modal report --}}
        <form action="{{ $editing ? route('employee/roles/update', $role->id) : route('employee/roles/store') }}" method="POST"
            class="ob-modal ob-role-form" novalidate>
            @csrf

            @if ($errors->any())
                <div class="ob-notice" role="alert">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <span>Data belum tersimpan. Periksa kembali isian yang ditandai merah.</span>
                </div>
            @endif

            <div class="row">
                <div class="col-12 ob-form-heading ob-form-heading--first">
                    <h3 class="ob-section-title"><i class="fas fa-id-badge" aria-hidden="true"></i> Informasi Role</h3>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Nama Role <span class="text-danger">*</span></label>
                    <input id="name" name="name" type="text" maxlength="125" autocomplete="off"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $role->name) }}" placeholder="Contoh: Supervisor" required autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Nama harus unik. Nama "super admin" dan "kasir" khusus untuk role sistem.</small>
                </div>
            </div>

            <div class="ob-form-actions">
                <a href="{{ route('employee/roles') }}" class="btn ob-btn ob-btn-ghost">Batal</a>
                <button type="submit" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-save" aria-hidden="true"></i> {{ $editing ? 'Simpan Perubahan' : 'Simpan Role' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('css')
    <style>
        .ob-role-form {
            padding: 1.25rem 1.5rem;
        }

        .ob-role-form .ob-notice {
            margin-bottom: 1.25rem;
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
