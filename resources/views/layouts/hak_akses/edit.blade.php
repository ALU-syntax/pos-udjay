@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    @php
        $isUser = $type === 'user';
        $roleName = $roleName ?? null;
        $selectedSet = array_flip((array) old('permissions', $selected));
        $inheritedSet = array_flip($inherited);
        $verbLabels = [
            'create' => 'Tambah', 'read' => 'Lihat', 'update' => 'Ubah', 'delete' => 'Hapus',
            'sort' => 'Urutkan', 'print' => 'Cetak', 'download' => 'Unduh', 'retry' => 'Coba Ulang',
            'cancel' => 'Batalkan', 'close' => 'Tutup', 'serve' => 'Sajikan',
        ];
        // Menu tanpa permission sama sekali tidak perlu ditampilkan
        $groups = $menus->filter(fn ($menu) => $menu->permissions->isNotEmpty()
            || $menu->subMenus->contains(fn ($sub) => $sub->permissions->isNotEmpty()));
        $shared = compact('selectedSet', 'inheritedSet', 'verbLabels', 'roleName');
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Employee · Hak Akses</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas {{ $isUser ? 'fa-user-plus' : 'fa-user-tag' }}"></i></span>
                    {{ $isUser ? 'Akses Tambahan: '.$subject->name : 'Hak Akses Role: '.$subject->name }}
                </h1>
                <p class="ob-header__desc">
                    @if ($isUser)
                        Role saat ini: <strong>{{ $roleName ?? 'Tanpa role' }}</strong>. Centang akses yang perlu ditambahkan khusus untuk user ini.
                    @else
                        Centang menu dan aksi yang boleh dipakai semua user dengan role ini.
                    @endif
                </p>
            </div>
            <a href="{{ route('employee/hak-akses') }}" class="btn ob-btn ob-btn-ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
            </a>
        </div>

        @if ($isUser && count($inherited))
            <div class="ob-notice" role="note">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <span>Akses bertanda gembok didapat dari role <strong>{{ $roleName }}</strong> dan hanya bisa diubah lewat Hak Akses Role.</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="ob-notice" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>Hak akses belum tersimpan: {{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ $action }}" method="POST" class="ob-perm-form">
            @csrf

            {{-- Toolbar --}}
            <div class="ob-card">
                <div class="ob-perm-toolbar">
                    <div class="ob-field ob-perm-search">
                        <label for="perm-search"><i class="fas fa-search" aria-hidden="true"></i> Cari menu</label>
                        <input id="perm-search" type="search" class="form-control" placeholder="Contoh: Laporan, Produk" autocomplete="off">
                    </div>
                    <label class="ob-perm-all" for="perm-select-all">
                        <input id="perm-select-all" type="checkbox" class="form-check-input">
                        <span>Pilih semua</span>
                    </label>
                    <span class="ob-perm-counter" aria-live="polite"><strong data-perm-count>0</strong> dari <span data-perm-total>0</span> akses dipilih</span>
                </div>
            </div>

            {{-- Daftar menu --}}
            @forelse ($groups as $menu)
                @php $groupId = 'menu-'.$menu->id; @endphp
                <section class="ob-card ob-perm-group" data-group-id="{{ $groupId }}">
                    <header class="ob-perm-group__head">
                        <label class="ob-perm-group__title" for="group-{{ $menu->id }}">
                            <input id="group-{{ $menu->id }}" type="checkbox" class="form-check-input perm-group-check" data-group="{{ $groupId }}">
                            @if ($menu->icon)
                                <i class="fas {{ $menu->icon }}" aria-hidden="true"></i>
                            @endif
                            <span>{{ $menu->name }}</span>
                            @unless ($menu->active)
                                <span class="ob-chip">Nonaktif</span>
                            @endunless
                        </label>
                        <span class="ob-perm-group__count" data-group-count="{{ $groupId }}"></span>
                    </header>

                    <div class="ob-perm-group__body">
                        @if ($menu->permissions->isNotEmpty())
                            @php $rowId = 'main-'.$menu->id; @endphp
                            <div class="ob-perm-row" data-row-id="{{ $rowId }}" data-search="{{ strtolower($menu->name) }}">
                                <label class="ob-perm-row__name" for="row-{{ $rowId }}">
                                    <input id="row-{{ $rowId }}" type="checkbox" class="form-check-input perm-row-check" data-row="{{ $rowId }}">
                                    <span>Menu utama</span>
                                </label>
                                <div class="ob-perm-row__items">
                                    @foreach ($menu->permissions as $permission)
                                        @include('layouts.hak_akses.permission-checkbox', $shared + compact('permission', 'groupId', 'rowId'))
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @foreach ($menu->subMenus->filter(fn ($sub) => $sub->permissions->isNotEmpty()) as $sub)
                            @php $rowId = 'sub-'.$sub->id; @endphp
                            <div class="ob-perm-row" data-row-id="{{ $rowId }}" data-search="{{ strtolower($sub->name) }}">
                                <label class="ob-perm-row__name" for="row-{{ $rowId }}">
                                    <input id="row-{{ $rowId }}" type="checkbox" class="form-check-input perm-row-check" data-row="{{ $rowId }}">
                                    <span>{{ $sub->name }}</span>
                                    @unless ($sub->active)
                                        <span class="ob-chip">Nonaktif</span>
                                    @endunless
                                </label>
                                <div class="ob-perm-row__items">
                                    @foreach ($sub->permissions as $permission)
                                        @include('layouts.hak_akses.permission-checkbox', $shared + compact('permission', 'groupId', 'rowId'))
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="ob-card ob-perm-empty">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    <span>Belum ada menu yang memiliki permission.</span>
                </div>
            @endforelse

            <div class="ob-card ob-perm-empty d-none" data-perm-no-result>
                <i class="fas fa-search" aria-hidden="true"></i>
                <span>Tidak ada menu yang cocok dengan pencarian.</span>
            </div>

            {{-- Aksi --}}
            <div class="ob-perm-actions">
                <span class="ob-cell-meta">Perubahan berlaku setelah disimpan.</span>
                <div class="ob-perm-actions__buttons">
                    <button type="reset" class="btn ob-btn ob-btn-ghost">Reset</button>
                    <button type="submit" class="btn ob-btn ob-btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Simpan Hak Akses
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            $(function () {
                const form = $('.ob-perm-form');
                const items = form.find('.perm-item');
                const selectAll = $('#perm-select-all');

                function byData(key, value) {
                    return items.filter((_, item) => String($(item).data(key)) === String(value));
                }

                // Centang penuh jika semua terpilih, setengah jika sebagian; kosong & nonaktif jika tidak ada yang bisa diubah
                function setState($checkbox, $items) {
                    const total = $items.length;
                    const checked = $items.filter(':checked').length;
                    $checkbox.prop({
                        checked: total > 0 && checked === total,
                        indeterminate: checked > 0 && checked < total,
                        disabled: total === 0,
                    });
                    return { total, checked };
                }

                function refresh() {
                    form.find('.perm-row-check').each(function () {
                        setState($(this), byData('row', $(this).data('row')));
                    });
                    form.find('.perm-group-check').each(function () {
                        const group = $(this).data('group');
                        const state = setState($(this), byData('group', group));
                        form.find('[data-group-count="' + group + '"]').text(state.checked + '/' + state.total);
                    });
                    const state = setState(selectAll, items);
                    form.find('[data-perm-count]').text(state.checked);
                    form.find('[data-perm-total]').text(state.total);
                }

                items.on('change', refresh);
                form.on('change', '.perm-row-check', function () {
                    byData('row', $(this).data('row')).prop('checked', this.checked);
                    refresh();
                });
                form.on('change', '.perm-group-check', function () {
                    byData('group', $(this).data('group')).prop('checked', this.checked);
                    refresh();
                });
                selectAll.on('change', function () {
                    items.prop('checked', this.checked);
                    refresh();
                });
                form.on('reset', () => setTimeout(refresh, 0));

                // Pencarian: nama menu utama yang cocok menampilkan seluruh grup, selain itu hanya submenu yang cocok
                $('#perm-search').on('input', function () {
                    const keyword = this.value.trim().toLowerCase();
                    let visibleGroups = 0;

                    form.find('.ob-perm-group').each(function () {
                        const group = $(this);
                        const rows = group.find('.ob-perm-row');
                        const groupName = group.find('.ob-perm-group__title span').first().text().toLowerCase();

                        if (!keyword || groupName.includes(keyword)) {
                            rows.removeClass('d-none');
                            group.removeClass('d-none');
                            visibleGroups++;
                            return;
                        }

                        rows.each(function () {
                            $(this).toggleClass('d-none', !String($(this).data('search')).includes(keyword));
                        });
                        const hasMatch = rows.not('.d-none').length > 0;
                        group.toggleClass('d-none', !hasMatch);
                        if (hasMatch) visibleGroups++;
                    });

                    form.find('[data-perm-no-result]').toggleClass('d-none', visibleGroups > 0 || !form.find('.ob-perm-group').length);
                });

                refresh();
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-perm-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                gap: 1rem 1.5rem;
                padding: 1rem 1.25rem;
            }

            .ob-perm-search {
                flex: 1 1 260px;
                max-width: 360px;
            }

            .ob-perm-all,
            .ob-perm-group__title,
            .ob-perm-row__name,
            .ob-perm {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                margin: 0;
                cursor: pointer;
            }

            .ob-perm-all {
                min-height: 42px;
                font-size: .875rem;
                font-weight: 600;
                color: var(--ob-text);
            }

            .ob-perm-counter {
                display: inline-flex;
                align-items: center;
                min-height: 42px;
                margin-left: auto;
                font-size: .875rem;
                color: var(--ob-text-2);
                font-variant-numeric: tabular-nums;
            }

            .ob-perm-group__head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
                padding: .85rem 1.25rem;
                border-bottom: 1px solid var(--ob-border);
                background: var(--ob-surface-2);
                border-radius: var(--ob-radius) var(--ob-radius) 0 0;
            }

            .ob-perm-group__title {
                font-size: .95rem;
                font-weight: 700;
                color: var(--ob-text);
            }

            .ob-perm-group__title .fas {
                color: var(--ob-muted);
            }

            .ob-perm-group__count {
                font-size: .78rem;
                font-weight: 600;
                color: var(--ob-muted);
                font-variant-numeric: tabular-nums;
            }

            .ob-perm-row {
                display: grid;
                grid-template-columns: minmax(180px, 260px) 1fr;
                align-items: center;
                gap: .5rem 1rem;
                padding: .7rem 1.25rem;
            }

            .ob-perm-row + .ob-perm-row {
                border-top: 1px solid var(--ob-border);
            }

            .ob-perm-row__name {
                font-size: .875rem;
                font-weight: 600;
                color: var(--ob-text-2);
            }

            .ob-perm-row__items {
                display: flex;
                flex-wrap: wrap;
                gap: .4rem;
            }

            .ob-perm {
                padding: .3rem .65rem;
                border: 1px solid var(--ob-border);
                border-radius: 999px;
                background: var(--ob-surface);
                font-size: .8rem;
                color: var(--ob-text-2);
                transition: border-color .15s ease, background-color .15s ease;
            }

            .ob-perm:has(.perm-item:checked) {
                border-color: var(--ob-primary);
                background: var(--ob-primary-soft);
                color: var(--ob-primary);
            }

            .ob-perm--inherited {
                border-style: dashed;
                background: var(--ob-surface-2);
                color: var(--ob-muted);
                cursor: not-allowed;
            }

            .ob-perm .form-check-input,
            .ob-perm-all .form-check-input,
            .ob-perm-group__title .form-check-input,
            .ob-perm-row__name .form-check-input {
                margin: 0;
                flex-shrink: 0;
            }

            .ob-perm-empty {
                display: flex;
                align-items: center;
                gap: .6rem;
                padding: 1.25rem;
                color: var(--ob-muted);
            }

            /* Tombol simpan tetap terlihat saat daftar menu panjang */
            .ob-perm-actions {
                position: sticky;
                bottom: 0;
                z-index: 5;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
                padding: .85rem 1.25rem;
                border: 1px solid var(--ob-border);
                border-radius: var(--ob-radius);
                background: var(--ob-surface);
                box-shadow: 0 -6px 20px rgba(15, 23, 42, .06);
            }

            .ob-perm-actions__buttons {
                display: flex;
                gap: .5rem;
            }

            @media (max-width: 575.98px) {
                .ob-perm-row {
                    grid-template-columns: 1fr;
                }

                .ob-perm-counter {
                    margin-left: 0;
                }
            }
        </style>
    @endpush
@endsection
