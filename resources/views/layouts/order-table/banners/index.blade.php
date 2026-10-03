@extends('layouts.app')

@section('content')
    <div class="main-content order-table-page">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div><h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-image me-2"></i>Banner Order Table</h2><p class="text-muted mb-0">Atur materi promosi dan tujuan banner pelanggan.</p></div>
            @can('create order-table/banners')<a href="{{ route('order-table/banners/create') }}" class="btn btn-primary btn-round action"><i class="fa fa-plus me-2"></i>Tambah Banner</a>@endcan
        </div>
        @if ($canManageGlobal)<div class="alert alert-warning border-0 shadow-sm"><i class="fa fa-triangle-exclamation me-2"></i>Perubahan banner global berdampak pada seluruh outlet aktif.</div>@endif
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h5 class="mb-1">Daftar Banner</h5><small class="text-muted">Status efektif memperhitungkan status dan periode tayang.</small></div><div class="order-table-filter"><label for="outlet-filter" class="form-label small mb-1">Filter outlet</label><select id="outlet-filter" class="form-select form-select-sm"><option value="">Semua outlet</option>@if ($canManageGlobal)<option value="global">Global</option>@endif @foreach ($outlets as $outlet)<option value="{{ $outlet->id }}">{{ $outlet->name }}</option>@endforeach</select></div></div>
            <div class="card-body"><div class="table-responsive">{!! $dataTable->table(['class' => 'table table-hover align-middle mb-0']) !!}</div></div>
        </div>
    </div>
    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const tableId = 'banner-table';
                const selectorUrls = { product: @json(route('order-table/selectors/products')), category: @json(route('order-table/selectors/categories')), voucher: @json(route('order-table/selectors/vouchers')), promo: @json(route('order-table/selectors/promos')), internal: @json(route('order-table/selectors/internal')) };
                $('#outlet-filter').on('change', () => window.LaravelDataTables[tableId].ajax.reload());
                handleAction(tableId, function () {
                    const form = $('#form_action'); const target = form.find('#action_target');
                    function initTarget(clear) {
                        const type = form.find('#action_type').val(); const selectable = type !== 'url';
                        const global = form.find('#outlet_id').val() === '';
                        form.find('#action_type option[value="product"], #action_type option[value="category"], #action_type option[value="promo"]').prop('disabled', global);
                        if (global && !['url', 'internal', 'voucher'].includes(type)) {
                            form.find('#action_type').val('url');
                        }
                        const effectiveType = form.find('#action_type').val();
                        const effectiveSelectable = effectiveType !== 'url';
                        if (target.hasClass('select2-hidden-accessible')) target.select2('destroy');
                        if (clear) target.empty();
                        form.find('.action-url-field').toggleClass('d-none', effectiveSelectable).find('input').prop('disabled', effectiveSelectable);
                        form.find('.action-target-field').toggleClass('d-none', !effectiveSelectable);
                        target.prop('disabled', !effectiveSelectable);
                        if (!effectiveSelectable) return;
                        target.select2({ dropdownParent: $('#modal_action'), width: '100%', placeholder: 'Pilih target', ajax: { url: selectorUrls[effectiveType], dataType: 'json', delay: 250, data: params => ({ q: params.term, outlet_id: form.find('#outlet_id').val() }), processResults: response => ({ results: response.data || [] }) } });
                    }
                    function preview(src) { form.find('.banner-image-preview').attr('src', src).toggleClass('d-none', !src); form.find('.banner-image-placeholder').toggleClass('d-none', !!src); }
                    function updateGlobalWarning() { form.find('.global-impact-warning').toggleClass('d-none', form.find('#outlet_id').val() !== ''); }
                    form.on('change', '#action_type, #outlet_id', function () { initTarget(true); updateGlobalWarning(); });
                    form.on('input', '#image_url', function () { if (this.value) form.find('#image').val(''); preview(this.value); });
                    form.on('change', '#image', function () { if (this.files[0]) { form.find('#image_url').val(''); preview(URL.createObjectURL(this.files[0])); } });
                    initTarget(false); updateGlobalWarning();
                });
                $('.main-content').on('click', '.toggle-banner', function (event) {
                    event.preventDefault(); const button = this;
                    Swal.fire({ title: 'Ubah status banner?', text: 'Perubahan langsung memengaruhi banner yang dilihat pelanggan.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, ubah status', cancelButtonText: 'Batal' }).then(function (result) {
                        if (!result.isConfirmed) return;
                        $.post(button.href, { _token: @json(csrf_token()) }).done(function (response) { showToast(response.status || 'success', response.message || 'Status banner diperbarui'); window.LaravelDataTables[tableId].ajax.reload(null, false); }).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui banner'));
                    });
                });
            });
        </script>
    @endpush
    @push('css')<style>.order-table-page .card{border-radius:10px}.order-table-filter{min-width:240px}.order-table-thumbnail{width:96px;height:54px;object-fit:cover;border-radius:6px}.order-table-actions{display:inline-flex;gap:5px}.order-table-actions .btn{width:34px;height:34px;padding:7px}</style>@endpush
@endsection
