<?php

namespace App\Exports\B2b;

use App\Services\B2b\ReportService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Workbook Laporan B2B dengan filter halaman: Ringkasan, Item terjual, Pesanan, Item pesanan, Piutang, Mutasi kas, Kontrak.
 * Daftar berisi data mentah (bukan 10 teratas) supaya bisa dipivot per customer / outlet / produk / kategori di Excel.
 */
class ReportExport implements WithMultipleSheets
{
    /**
     * @param  array  $data  ReportService::exportData()
     * @param  list<string>  $filters  keterangan filter, mis. "Periode: 01/11/2026 – 23/11/2026"
     */
    public function __construct(
        private readonly array $data,
        private readonly array $filters,
    ) {}

    public function sheets(): array
    {
        $notes = [
            'Filter: '.implode(' · ', $this->filters),
            'Diekspor '.now()->format('d/m/Y H:i').'. Penjualan = pesanan dikonfirmasi + selesai; piutang, umur piutang, dan kontrak per hari ini.',
        ];
        $data = $this->data;

        return [
            new ReportSheet('Ringkasan', 'Laporan B2B', $notes, $this->summaryTables()),
            new ReportSheet('Item terjual', 'Item terjual', $notes, [[
                'columns' => [['No', 6, 'int'], ['Produk', 30], ['Varian', 24], ['Kategori', 20], ['Qty Terjual', 12, 'int'], ['Pesanan', 10, 'int'],
                    ['Customer', 10, 'int'], ['Harga Rata-rata', 16, 'money'], ['Total', 17, 'money']],
                'rows' => array_map(fn (array $item, int $index) => [$index + 1, $item['product'], $item['variant'], $item['category'], $item['qty'],
                    $item['orders'], $item['customers'], $item['average_price'], $item['amount']], $data['sold_items'], array_keys($data['sold_items'])),
                'total' => 'Total', 'sum' => [4, 8], 'empty' => 'Tidak ada item terjual di periode ini.',
            ]]),
            new ReportSheet('Pesanan', 'Pesanan (penjualan)', $notes, [[
                'columns' => [['No', 6, 'int'], ['No. Pesanan', 22], ['Tanggal', 17, 'datetime'], ['Customer', 30], ['Kode Customer', 16],
                    ['Outlet', 22], ['Pembayaran', 15], ['Status', 14], ['Total', 16, 'money']],
                'rows' => array_map(fn (array $order, int $index) => [$index + 1, $order['order_number'], $order['order_at'], $order['customer'],
                    $order['customer_code'], $order['outlet'], $order['payment_type'], $order['status'], $order['total']], $data['orders'], array_keys($data['orders'])),
                'total' => 'Total', 'sum' => [8], 'empty' => 'Tidak ada penjualan di periode ini.',
            ]]),
            new ReportSheet('Item pesanan', 'Item pesanan (penjualan)', $notes, [[
                'columns' => [['No. Pesanan', 22], ['Tanggal', 17, 'datetime'], ['Customer', 28], ['Outlet', 20], ['Produk', 28], ['Varian', 22],
                    ['Kategori', 18], ['Qty', 8, 'int'], ['Harga Normal', 15, 'money'], ['Harga', 15, 'money'], ['Harga Kontrak', 10], ['Total', 16, 'money']],
                'rows' => array_map(fn (array $item) => [$item['order_number'], $item['order_at'], $item['customer'], $item['outlet'], $item['product'],
                    $item['variant'], $item['category'], $item['qty'], $item['base_price'], $item['price'], $item['contract_price'] ? 'Ya' : 'Tidak', $item['total']], $data['items']),
                'total' => 'Total', 'sum' => [7, 11], 'empty' => 'Tidak ada penjualan di periode ini.',
            ]]),
            new ReportSheet('Piutang', 'Invoice belum lunas per '.now()->format('d/m/Y'), $notes, [[
                'columns' => [['No. Invoice', 22], ['No. Pesanan', 22], ['Customer', 30], ['Kode Customer', 16], ['Outlet', 20], ['Tgl Invoice', 13, 'date'],
                    ['Jatuh Tempo', 13, 'date'], ['Telat (hari)', 11, 'int'], ['Umur', 18], ['Total', 15, 'money'], ['Dibayar', 15, 'money'], ['Sisa Tagihan', 15, 'money']],
                'rows' => array_map(fn (array $invoice) => [$invoice['invoice_number'], $invoice['order_number'], $invoice['customer'], $invoice['customer_code'],
                    $invoice['outlet'], $invoice['issue_at'], $invoice['due_at'], max(0, $invoice['days_overdue']), $invoice['bucket'],
                    $invoice['total'], $invoice['paid'], $invoice['outstanding']], $data['receivables']),
                'total' => 'Total', 'sum' => [9, 10, 11], 'empty' => 'Tidak ada invoice yang belum lunas.',
            ]]),
            new ReportSheet('Mutasi kas', 'Mutasi kas (pembayaran dan refund)', $notes, [[
                'columns' => [['Tanggal', 17, 'datetime'], ['Jenis', 12], ['No. Pembayaran', 22], ['Customer', 30], ['No. Invoice', 22], ['Metode', 24],
                    ['Outlet Penerima', 22], ['Kasir', 20], ['Masuk', 15, 'money'], ['Refund', 15, 'money'], ['Keterangan', 18]],
                'rows' => array_map(fn (array $item) => [$item['at'], $item['type'] === 'refund' ? 'Refund' : 'Pembayaran', $item['number'], $item['customer'],
                    $item['invoice_number'], $item['method'], $item['outlet'], $item['cashier'],
                    $item['type'] === 'refund' ? null : $item['amount'], $item['type'] === 'refund' ? -$item['amount'] : null,
                    $item['refunded'] ? 'Sudah direfund' : null], $data['mutations']),
                'total' => 'Total', 'sum' => [8, 9], 'empty' => 'Tidak ada pembayaran atau refund di periode ini.',
            ]]),
            new ReportSheet('Kontrak', 'Kontrak hampir berakhir (≤ '.ReportService::CONTRACT_ENDING_DAYS.' hari) per '.now()->format('d/m/Y'), $notes, [[
                'columns' => [['Customer', 30], ['Kode Customer', 16], ['No. Kontrak', 24], ['Berakhir', 13, 'date'], ['Sisa (hari)', 11, 'int'],
                    ['Kontrak Lanjutan', 24], ['Penjualan Kontrak', 17, 'money'], ['Jumlah Pesanan', 12, 'int']],
                'rows' => array_map(fn (array $contract) => [$contract['customer'], $contract['customer_code'], $contract['contract_number'], $contract['end_at'],
                    $contract['days_left'], $contract['successor'] ? $contract['successor']['contract_number'].' (mulai '.$contract['successor']['start_date'].')' : 'Belum ada',
                    $contract['sales']['amount'], $contract['sales']['orders']], $data['contracts']),
                'empty' => 'Tidak ada kontrak yang berakhir dalam '.ReportService::CONTRACT_ENDING_DAYS.' hari ke depan.',
            ]]),
        ];
    }

    /** Sheet Ringkasan: KPI, umur piutang, rekap kas per metode / outlet penerima / kasir. */
    private function summaryTables(): array
    {
        $summary = $this->data['summary'];
        $cashGroups = ['method' => 'Metode', 'outlet' => 'Outlet Penerima', 'cashier' => 'Kasir'];

        $tables = [[
            'title' => 'Ringkasan',
            'columns' => [['Angka', 34], ['Nilai', 18, 'money'], ['Jumlah', 14, 'int'], ['Keterangan', 40]],
            'rows' => [
                ['Penjualan', $summary['sales']['amount'], $summary['sales']['orders'], $summary['sales']['completed'].' selesai · '.$summary['sales']['confirmed'].' dikonfirmasi'],
                ['Uang masuk', $summary['cash']['received'], $summary['cash']['received_count'], 'Pembayaran diterima'],
                ['Refund', $summary['cash']['refunded'], $summary['cash']['refund_count'], 'Uang dikembalikan'],
                ['Kas masuk bersih', $summary['cash']['net'], null, 'Uang masuk − refund'],
                ['Piutang berjalan (per hari ini)', $summary['receivable']['amount'], $summary['receivable']['invoices'], $summary['receivable']['customers'].' customer'],
                ['Lewat jatuh tempo (per hari ini)', $summary['overdue']['amount'], $summary['overdue']['invoices'], $summary['overdue']['customers'].' customer'],
                ['Pesanan dibatalkan', $summary['cancelled']['amount'], $summary['cancelled']['orders'], 'Tidak dihitung sebagai penjualan'],
            ],
        ], [
            'title' => 'Umur piutang (per hari ini)',
            'columns' => [['Umur', 34], ['Sisa Tagihan', 18, 'money'], ['Invoice', 14, 'int'], ['Customer', 40, 'int']],
            'rows' => array_map(fn (array $bucket) => [$bucket['label'], $bucket['amount'], $bucket['invoices'], $bucket['customers']], $this->data['aging']['buckets']),
            'total' => 'Total', 'sum' => [1, 2],
        ]];

        foreach ($cashGroups as $by => $label) {
            $tables[] = [
                'title' => 'Kas per '.strtolower($label),
                'columns' => [[$label, 34], ['Masuk', 18, 'money'], ['Refund', 14, 'money'], ['Bersih', 18, 'money']],
                'rows' => array_map(fn (array $item) => [$item['label'], $item['received'], $item['refunded'], $item['net']], $this->data['cash'][$by]['items']),
                'total' => 'Total', 'sum' => [1, 2, 3], 'empty' => 'Tidak ada pembayaran atau refund di periode ini.',
            ];
        }

        return $tables;
    }
}
