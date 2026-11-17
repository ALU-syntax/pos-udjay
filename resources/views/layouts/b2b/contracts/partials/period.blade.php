{{-- Periode kontrak + bilah progres. Dipakai di daftar (sel tabel) dan halaman detail. --}}
@php
    $variant = $contract->statusVariant();
    $hintClass = $contract->isEndingSoon() ? 'is-warning' : '';
@endphp
<div class="b2b-period {{ ($large ?? false) ? 'b2b-period--lg' : '' }}">
    <div class="b2b-period__dates">
        <span>{{ $contract->start_date->translatedFormat('d M Y') }}</span>
        <i class="fas fa-arrow-right" aria-hidden="true"></i>
        <span>{{ $contract->end_date->translatedFormat('d M Y') }}</span>
    </div>
    <div class="b2b-period__bar b2b-period__bar--{{ $variant }}" role="progressbar" aria-valuemin="0" aria-valuemax="100"
        aria-valuenow="{{ $contract->periodProgress() }}" aria-label="Periode berjalan {{ $contract->periodProgress() }}%">
        <span style="width: {{ $contract->isTerminated() ? 100 : $contract->periodProgress() }}%"></span>
    </div>
    <span class="b2b-period__hint {{ $hintClass }}">{{ $contract->periodHint() }}</span>
</div>
