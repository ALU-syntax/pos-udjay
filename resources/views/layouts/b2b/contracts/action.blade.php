<div class="ob-row-actions" role="group" aria-label="Aksi kontrak {{ $contract->contract_number }}">
    <a href="{{ route('b2b/contracts/show', $contract->id) }}" class="ob-icon-btn ob-icon-btn--sm" title="Detail kontrak" aria-label="Detail kontrak">
        <i class="fas fa-eye" aria-hidden="true"></i>
    </a>
    @if (!$contract->isTerminated())
        @can('update b2b/contracts')
            <a href="{{ route('b2b/contracts/edit', $contract->id) }}" class="ob-icon-btn ob-icon-btn--sm" title="Edit kontrak" aria-label="Edit kontrak">
                <i class="fas fa-pen" aria-hidden="true"></i>
            </a>
        @endcan
    @endif
    @can('create b2b/contracts')
        <a href="{{ route('b2b/contracts/create', ['copy_from' => $contract->id]) }}" class="ob-icon-btn ob-icon-btn--sm" title="Perpanjang (salin ke kontrak baru)" aria-label="Perpanjang kontrak">
            <i class="fas fa-copy" aria-hidden="true"></i>
        </a>
    @endcan
</div>
