<div class="ob-row-actions" role="group" aria-label="Aksi kontrak {{ $contract->contract_number }}">
    <a href="{{ route('b2b/contracts/show', $contract->id) }}" class="ob-icon-btn ob-icon-btn--sm" title="Detail kontrak" aria-label="Detail kontrak">
        <i class="fas fa-eye" aria-hidden="true"></i>
    </a>
    @if ($contract->isDraft())
        @can('update b2b/contracts')
            <a href="{{ route('b2b/contracts/edit', $contract->id) }}" class="ob-icon-btn ob-icon-btn--sm" title="Edit kontrak" aria-label="Edit kontrak">
                <i class="fas fa-pen" aria-hidden="true"></i>
            </a>
        @endcan
        @can('activate b2b/contracts')
            <a href="{{ route('b2b/contracts/activate', $contract->id) }}" data-b2b-confirm data-table="b2b-contract-table"
                data-title="Aktifkan kontrak?" data-text="Setelah aktif, diskon kontrak tidak bisa diedit." data-confirm-text="Ya, aktifkan"
                class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--success" title="Aktifkan" aria-label="Aktifkan kontrak">
                <i class="fas fa-check" aria-hidden="true"></i>
            </a>
        @endcan
    @elseif (in_array($contract->status, ['active', 'expired', 'terminated'], true))
        @can('create b2b/contracts')
            <a href="{{ route('b2b/contracts/create', ['copy_from' => $contract->id]) }}" class="ob-icon-btn ob-icon-btn--sm" title="Perpanjang (salin ke kontrak baru)" aria-label="Perpanjang kontrak">
                <i class="fas fa-copy" aria-hidden="true"></i>
            </a>
        @endcan
    @endif
</div>
