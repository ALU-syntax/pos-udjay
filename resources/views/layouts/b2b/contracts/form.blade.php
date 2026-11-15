@extends('layouts.app')

@php
    $editing = $contract->exists;
    $ruleRow = fn ($rule = null) => view('layouts.b2b.contracts.partials.rule-row', ['rule' => $rule, 'outlets' => $outlets])->render();
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow"><a href="{{ route('b2b/contracts') }}">Kontrak B2B</a></div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-file-signature"></i></span>
                    {{ $editing ? 'Edit Kontrak '.$contract->contract_number : 'Buat Kontrak' }}
                </h1>
                <p class="ob-header__desc">
                    @if ($copiedFrom)
                        Disalin dari {{ $copiedFrom->contract_number }}. Periode otomatis disambung setelah kontrak lama berakhir.
                    @else
                        Kontrak menentukan diskon customer. Satu customer hanya boleh punya satu kontrak berlaku pada satu waktu.
                    @endif
                </p>
            </div>
        </div>

        <div class="ob-notice mb-3" role="note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <span>Aturan yang paling spesifik menang: <strong>varian → produk → kategori → semua produk</strong>. Di level yang sama, aturan khusus outlet menang, lalu qty minimal terbesar. Setelah aktif, diskon tidak bisa diedit.</span>
        </div>

        <form id="contract-form" action="{{ $editing ? route('b2b/contracts/update', $contract->id) : route('b2b/contracts/store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="ob-card mb-3">
                <h2 class="ob-section-title"><i class="fas fa-handshake" aria-hidden="true"></i> Customer &amp; Periode</h2>
                <div class="row">
                    <div class="col-md-6 mb-3 b2b-field">
                        <label for="b2b_customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                        <select id="b2b_customer_id" name="b2b_customer_id" class="form-select" required>
                            <option value="">Pilih customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((int) $contract->b2b_customer_id === $customer->id)>{{ $customer->name }} · {{ $customer->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6 mb-3 b2b-field">
                        <label for="start_date" class="form-label">Mulai <span class="text-danger">*</span></label>
                        <input id="start_date" name="start_date" type="date" class="form-control" value="{{ $contract->start_date?->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3 col-6 mb-3 b2b-field">
                        <label for="end_date" class="form-label">Berakhir <span class="text-danger">*</span></label>
                        <input id="end_date" name="end_date" type="date" class="form-control" value="{{ $contract->end_date?->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-12 mb-3 b2b-field">
                        <label for="attachment" class="form-label">File kontrak <span class="text-muted fw-normal">(PDF/JPG/PNG/DOC, maks. 5 MB)</span></label>
                        <input id="attachment" name="attachment" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        @if ($contract->attachment_name)
                            <small class="text-muted">File saat ini: {{ $contract->attachment_name }}. Pilih file baru untuk mengganti.</small>
                        @endif
                    </div>
                    <div class="col-12 mb-2 b2b-field">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="2000">{{ $contract->notes }}</textarea>
                    </div>
                </div>
            </div>

            <div class="ob-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="ob-section-title mb-0"><i class="fas fa-percent" aria-hidden="true"></i> Aturan Diskon</h2>
                    <button type="button" class="btn ob-btn ob-btn-outline btn-sm" id="add-rule"><i class="fas fa-plus" aria-hidden="true"></i> Tambah aturan</button>
                </div>
                <div data-error-for="rules"></div>
                <div class="b2b-repeater" id="rules" data-prefix="rules">
                    @foreach ($contract->rules as $rule)
                        {!! $ruleRow($rule) !!}
                    @endforeach
                </div>
                <div class="b2b-repeater-empty" data-empty-for="rules">Belum ada aturan. Kontrak wajib punya minimal satu aturan diskon.</div>
            </div>

            <div class="b2b-form-footer">
                @if (!$editing)
                    @can('activate b2b/contracts')
                        <label class="b2b-flag me-auto"><input type="checkbox" name="activate" value="1"> Langsung aktifkan setelah disimpan</label>
                    @endcan
                @endif
                <a href="{{ $editing ? route('b2b/contracts/show', $contract->id) : route('b2b/contracts') }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1" aria-hidden="true"></i> Simpan</button>
            </div>
        </form>
    </div>

    <template id="template-rule">{!! $ruleRow() !!}</template>

    @push('js')
        <script>
            $(function () {
                const form = $('#contract-form');
                const urls = {
                    category: @json(route('b2b/selectors/categories')),
                    product: @json(route('b2b/selectors/products')),
                    variant: @json(route('b2b/selectors/variants')),
                };
                const targetField = { category: 'category_id', product: 'product_id', variant: 'variant_id' };
                const prefixes = { percent: '%', nominal: 'Rp', fixed_price: 'Rp' };

                $('#b2b_customer_id').select2({ width: '100%', placeholder: 'Pilih customer' });

                function initRow(row, resetTarget) {
                    const scope = row.find('.rule-scope').val();
                    const target = row.find('.rule-target');
                    const outlet = row.find('.rule-outlet');
                    if (target.hasClass('select2-hidden-accessible')) target.select2('destroy');
                    if (resetTarget) target.empty();

                    row.find('.rule-target-field').toggleClass('d-none', scope === 'all');
                    row.find('.rule-outlet-hint').toggleClass('d-none', !['product', 'variant'].includes(scope));
                    row.find('.rule-prefix').text(prefixes[row.find('.rule-type').val()] || '%');
                    if (scope === 'all') return;

                    target.attr('data-field', targetField[scope]);
                    target.select2({
                        width: '100%',
                        placeholder: { category: 'Cari kategori', product: 'Cari produk', variant: 'Cari produk/varian' }[scope],
                        ajax: {
                            url: urls[scope], dataType: 'json', delay: 250,
                            data: params => ({ q: params.term, outlet_id: scope === 'category' ? '' : outlet.val() }),
                            processResults: response => ({ results: response.data || [] }),
                        },
                    });
                }

                function refresh() {
                    const container = $('#rules');
                    $('[data-empty-for="rules"]').toggleClass('d-none', container.children().length > 0);
                    // Baris cakupan "semua" tidak mengirim target.
                    container.find('.b2b-rule-row').each(function () {
                        const row = $(this);
                        row.find('.rule-target').prop('disabled', row.find('.rule-scope').val() === 'all');
                    });
                    B2b.reindex(container, 'rules');
                }

                $('#add-rule').on('click', function () {
                    const row = $($('#template-rule').html());
                    $('#rules').append(row);
                    initRow(row, true);
                    refresh();
                });

                form.on('click', '[data-remove-row]', function () {
                    $(this).closest('.b2b-repeater-row').remove();
                    refresh();
                });
                form.on('change', '.rule-scope', function () { initRow($(this).closest('.b2b-rule-row'), true); refresh(); });
                form.on('change', '.rule-outlet', function () {
                    const row = $(this).closest('.b2b-rule-row');
                    if (['product', 'variant'].includes(row.find('.rule-scope').val())) initRow(row, true);
                });
                form.on('change', '.rule-type', function () {
                    const row = $(this).closest('.b2b-rule-row');
                    row.find('.rule-prefix').text(prefixes[this.value] || '%');
                });

                form.on('submit', function (event) {
                    event.preventDefault();
                    refresh();
                    B2b.submit(this);
                });

                $('#rules .b2b-rule-row').each(function () { initRow($(this), false); });
                refresh();
            });
        </script>
    @endpush
@endsection
