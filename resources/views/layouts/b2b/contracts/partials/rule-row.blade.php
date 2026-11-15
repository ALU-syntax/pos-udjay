{{-- Baris aturan diskon kontrak. Nama input diberikan B2b.reindex() (rules[i][field]). --}}
@php
    $scope = $rule?->scope ?? 'all';
    $targetField = ['category' => 'category_id', 'product' => 'product_id', 'variant' => 'variant_id'][$scope] ?? 'category_id';
    $targetId = $rule ? ($rule->{$targetField} ?? null) : null;
@endphp
<div class="b2b-repeater-row b2b-rule-row">
    <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger b2b-repeater-row__remove" data-remove-row title="Hapus aturan" aria-label="Hapus aturan">
        <i class="fas fa-trash" aria-hidden="true"></i>
    </button>
    <div class="row">
        <div class="col-md-3 col-6 mb-3 b2b-field">
            <label class="form-label">Cakupan</label>
            <select data-field="scope" class="form-select rule-scope">
                <option value="all" @selected($scope === 'all')>Semua produk</option>
                <option value="category" @selected($scope === 'category')>Kategori</option>
                <option value="product" @selected($scope === 'product')>Produk</option>
                <option value="variant" @selected($scope === 'variant')>Varian</option>
            </select>
        </div>
        <div class="col-md-3 col-6 mb-3 b2b-field">
            <label class="form-label">Outlet</label>
            <select data-field="outlet_id" class="form-select rule-outlet">
                <option value="">Semua outlet</option>
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet->id }}" @selected((int) ($rule?->outlet_id) === $outlet->id)>{{ $outlet->name }}</option>
                @endforeach
            </select>
            <small class="text-muted rule-outlet-hint">Produk/varian otomatis mengikuti outletnya.</small>
        </div>
        <div class="col-md-6 mb-3 b2b-field rule-target-field">
            <label class="form-label">Target <span class="text-danger">*</span></label>
            <select data-field="{{ $targetField }}" class="form-select rule-target">
                @if ($targetId)
                    <option value="{{ $targetId }}" selected>{{ $rule->targetLabel() }}</option>
                @endif
            </select>
        </div>
        <div class="col-md-4 col-6 mb-3 b2b-field">
            <label class="form-label">Tipe diskon</label>
            <select data-field="discount_type" class="form-select rule-type">
                <option value="percent" @selected(($rule?->discount_type ?? 'percent') === 'percent')>Diskon persen</option>
                <option value="nominal" @selected($rule?->discount_type === 'nominal')>Potongan per unit</option>
                <option value="fixed_price" @selected($rule?->discount_type === 'fixed_price')>Harga khusus per unit</option>
            </select>
        </div>
        <div class="col-md-4 col-6 mb-3 b2b-field">
            <label class="form-label">Nilai <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text rule-prefix">%</span>
                <input data-field="discount_value" type="number" min="0" step="0.01" class="form-control" value="{{ $rule ? rtrim(rtrim(number_format($rule->discount_value, 2, '.', ''), '0'), '.') : '' }}">
            </div>
        </div>
        <div class="col-md-4 mb-3 b2b-field">
            <label class="form-label">Berlaku mulai qty</label>
            <input data-field="min_qty" type="number" min="1" class="form-control" value="{{ $rule?->min_qty ?? 1 }}">
            <small class="text-muted">Untuk harga bertingkat, buat beberapa baris dengan qty berbeda.</small>
        </div>
    </div>
</div>
