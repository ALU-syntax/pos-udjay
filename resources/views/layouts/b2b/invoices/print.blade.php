<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    @php
        $order = $invoice->order;
        $outlet = $order->outlet;
        $billing = $invoice->billing_address_snapshot;
        $shipping = $order->shipping_address_snapshot;
        $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    @endphp
    <style>
        :root { --text: #0f172a; --muted: #64748b; --border: #e2e8f0; --accent: #b91c1c; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: var(--text); font: 13px/1.5 "Public Sans", -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: .5rem; padding: 12px 16px; }
        .toolbar button, .toolbar a { padding: 10px 18px; border-radius: 8px; border: 1px solid var(--border); background: #fff; color: var(--text); font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .toolbar button.primary { background: #1572e8; border-color: #1572e8; color: #fff; }
        .sheet { width: 210mm; max-width: 100%; min-height: 297mm; margin: 0 auto 24px; padding: 18mm 16mm; background: #fff; box-shadow: 0 4px 24px rgba(15, 23, 42, .08); }
        .head { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 16px; border-bottom: 2px solid var(--text); }
        .brand img { height: 42px; margin-bottom: 8px; }
        .brand strong { display: block; font-size: 15px; }
        .brand div, .muted { color: var(--muted); }
        .title { text-align: right; }
        .title h1 { margin: 0 0 4px; font-size: 26px; letter-spacing: .08em; }
        .status { display: inline-block; margin-top: 6px; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border: 1px solid currentColor; }
        .status--void { color: var(--accent); }
        .status--paid { color: #15803d; }
        .status--open { color: #b45309; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 18px 0; }
        .label { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { padding: 8px 6px; border-bottom: 1px solid var(--text); font-size: 10px; letter-spacing: .06em; text-transform: uppercase; text-align: left; }
        td { padding: 8px 6px; border-bottom: 1px solid var(--border); vertical-align: top; }
        .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .totals { width: 300px; margin-left: auto; margin-top: 12px; }
        .totals div { display: flex; justify-content: space-between; padding: 5px 0; }
        .totals .grand { margin-top: 4px; padding-top: 8px; border-top: 2px solid var(--text); font-size: 16px; font-weight: 700; }
        .due { margin-top: 20px; padding: 12px 14px; border: 1px solid var(--border); border-left: 4px solid var(--text); border-radius: 6px; }
        .foot { margin-top: 36px; display: flex; justify-content: space-between; gap: 24px; }
        .sign { width: 200px; text-align: center; }
        .sign .line { margin-top: 64px; border-top: 1px solid var(--text); padding-top: 4px; }
        .void-mark { position: fixed; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-24deg); font-size: 96px; font-weight: 800; color: rgba(185, 28, 28, .12); letter-spacing: .1em; pointer-events: none; }
        @media (max-width: 720px) {
            .sheet { padding: 16px; min-height: 0; }
            .head, .foot { flex-direction: column; }
            .title { text-align: left; }
            .grid { grid-template-columns: 1fr; }
            .totals { width: 100%; }
        }
        @media print {
            @page { size: A4; margin: 0; }
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; margin: 0; width: auto; min-height: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <button type="button" onclick="window.close()">Tutup</button>
    </div>

    @if ($invoice->status === 'void')
        <div class="void-mark" aria-hidden="true">BATAL</div>
    @endif

    <main class="sheet">
        <header class="head">
            <div class="brand">
                <img src="{{ asset('img/Logo Red.png') }}" alt="">
                <strong>{{ $outlet?->name ?? config('app.name') }}</strong>
                <div>{{ $outlet?->address }}</div>
                @if ($outlet?->phone)<div>Telp. {{ $outlet->phone }}</div>@endif
            </div>
            <div class="title">
                <h1>INVOICE</h1>
                <div><strong>{{ $invoice->invoice_number }}</strong></div>
                <div class="muted">Pesanan {{ $order->order_number }}</div>
                @php
                    $statusClass = match ($invoice->status) { 'void' => 'void', 'paid' => 'paid', default => 'open' };
                @endphp
                <span class="status status--{{ $statusClass }}">{{ $invoice->statusLabel() }}</span>
            </div>
        </header>

        <section class="grid">
            <div>
                <div class="label">Ditagihkan kepada</div>
                <strong>{{ $invoice->customer?->name }}</strong>
                @if ($invoice->customer?->npwp)<div class="muted">NPWP {{ $invoice->customer->npwp }}</div>@endif
                @if ($billing)
                    <div>{{ $billing['full_address'] ?? '' }}</div>
                    @if (!empty($billing['recipient_name']))<div class="muted">u.p. {{ $billing['recipient_name'] }}</div>@endif
                @endif
                @if ($invoice->customer?->phone)<div class="muted">{{ $invoice->customer->phone }}</div>@endif
            </div>
            <div>
                <div class="label">Dikirim ke</div>
                @if ($shipping)
                    <strong>{{ $shipping['label'] ?? '' }}</strong>
                    <div>{{ $shipping['full_address'] ?? '' }}</div>
                    @if (!empty($shipping['recipient_name']))<div class="muted">{{ $shipping['recipient_name'] }} {{ $shipping['phone'] ?? '' }}</div>@endif
                @else
                    <div>{{ $order->deliveryLabel() }}</div>
                @endif
            </div>
            <div>
                <div class="label">Tanggal invoice</div>
                <div>{{ $invoice->issue_date->translatedFormat('d F Y') }}</div>
                <div class="label" style="margin-top:8px">Jatuh tempo</div>
                <div><strong>{{ $invoice->due_date->translatedFormat('d F Y') }}</strong></div>
                @if ($order->contract)
                    <div class="label" style="margin-top:8px">Kontrak</div>
                    <div>{{ $order->contract->contract_number }}</div>
                @endif
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width:36px">No</th>
                    <th>Produk</th>
                    <th class="num">Qty</th>
                    <th class="num">Harga</th>
                    <th class="num">Diskon/unit</th>
                    <th class="num">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            {{ $item->displayName() }}
                            @if ($item->notes)<div class="muted">{{ $item->notes }}</div>@endif
                        </td>
                        <td class="num">{{ number_format($item->qty, 0, ',', '.') }}</td>
                        <td class="num">{{ $rupiah($item->base_price) }}</td>
                        <td class="num">{{ $item->discount_per_unit ? $rupiah($item->discount_per_unit) : '-' }}</td>
                        <td class="num">{{ $rupiah($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><span>Subtotal</span><span class="num">{{ $rupiah($order->subtotal) }}</span></div>
            @if ($order->discount_total)
                <div><span>Diskon kontrak</span><span class="num">− {{ $rupiah($order->discount_total) }}</span></div>
            @endif
            @if ($order->shipping_cost)
                <div><span>Ongkos kirim</span><span class="num">{{ $rupiah($order->shipping_cost) }}</span></div>
            @endif
            @if ($invoice->unique_code_amount)
                <div><span>Kode unik</span><span class="num">{{ $rupiah($invoice->unique_code_amount) }}</span></div>
            @endif
            <div class="grand"><span>Total tagihan</span><span class="num">{{ $rupiah($invoice->total_to_pay) }}</span></div>
            @if ($invoice->paid_amount)
                <div><span>Sudah dibayar</span><span class="num">{{ $rupiah($invoice->paid_amount) }}</span></div>
                <div><strong>Sisa tagihan</strong><strong class="num">{{ $rupiah($invoice->outstandingAmount()) }}</strong></div>
            @endif
        </div>

        @if ($invoice->status !== 'void')
            <div class="due">
                Mohon lakukan pembayaran sebesar <strong>{{ $rupiah($invoice->outstandingAmount()) }}</strong>
                paling lambat <strong>{{ $invoice->due_date->translatedFormat('d F Y') }}</strong>
                dan cantumkan nomor invoice <strong>{{ $invoice->invoice_number }}</strong> pada keterangan pembayaran.
            </div>
        @else
            <div class="due">Invoice ini dibatalkan{{ $invoice->void_reason ? ': '.$invoice->void_reason : '' }}.</div>
        @endif

        @if ($order->notes)
            <p class="muted" style="margin-top:16px"><strong>Catatan:</strong> {{ $order->notes }}</p>
        @endif

        <footer class="foot">
            <div class="muted">Dicetak {{ now()->translatedFormat('d F Y H:i') }}</div>
            <div class="sign">
                <div>Hormat kami,</div>
                <div class="line">{{ $outlet?->name ?? config('app.name') }}</div>
            </div>
        </footer>
    </main>
</body>
</html>
