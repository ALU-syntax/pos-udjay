<?php

namespace App\Exports\B2b;

use App\Models\B2b\Customer;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Kartu piutang satu customer: ringkasan, invoice belum lunas (per hari ini), dan mutasi tagihan periode dengan saldo. */
class CustomerCardExport implements WithMultipleSheets
{
    private const TYPE_LABELS = ['invoice' => 'Invoice', 'payment' => 'Pembayaran', 'void' => 'Invoice dibatalkan', 'refund' => 'Refund'];

    /**
     * @param  array  $card  ReportService::customerCard()
     * @param  list<string>  $filters
     */
    public function __construct(
        private readonly Customer $customer,
        private readonly array $card,
        private readonly array $filters,
    ) {}

    public static function filename(Customer $customer, string $from, string $to): string
    {
        $code = preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) ($customer->code ?: $customer->id));

        return "Kartu Piutang {$code} {$from} sd {$to}.xlsx";
    }

    public function sheets(): array
    {
        $card = $this->card;
        $ledger = $card['ledger'];
        $notes = [
            trim(implode(' · ', array_filter([$this->customer->code, $this->customer->contact_name, $this->customer->phone, $this->customer->email]))),
            'Filter: '.implode(' · ', $this->filters),
            'Diekspor '.now()->format('d/m/Y H:i').'. Pelunasan salah catat yang dibatalkan tidak dihitung.',
        ];

        $rows = [[$ledger['from_at'], 'Saldo awal', null, null, null, null, $ledger['opening']]];
        foreach ($ledger['rows'] as $row) {
            $rows[] = [$row['at'], self::TYPE_LABELS[$row['type']], $row['number'], $row['note'], $row['debit'] ?: null, $row['credit'] ?: null, $row['balance']];
        }

        return [new ReportSheet('Kartu piutang', 'Kartu Piutang '.$this->customer->name, array_values(array_filter($notes)), [[
            'title' => 'Ringkasan',
            'columns' => [['Angka', 30], ['Nilai', 18, 'money'], ['Jumlah', 14, 'int']],
            'rows' => [
                ['Sisa piutang (per hari ini)', $card['receivable']['amount'], $card['receivable']['invoices']],
                ['Lewat jatuh tempo (per hari ini)', $card['overdue']['amount'], $card['overdue']['invoices']],
                ['Penjualan periode', $card['sales']['amount'], $card['sales']['orders']],
                ['Kas masuk bersih periode', $card['cash']['net'], null],
            ],
        ], [
            'title' => 'Invoice belum lunas (per hari ini)',
            'columns' => [['No. Invoice', 22], ['Tgl Invoice', 13, 'date'], ['Jatuh Tempo', 13, 'date'], ['Telat (hari)', 11, 'int'],
                ['Total', 15, 'money'], ['Dibayar', 15, 'money'], ['Sisa', 15, 'money']],
            'rows' => array_map(fn (array $invoice) => [$invoice['invoice_number'], $invoice['issue_at'], $invoice['due_at'], max(0, $invoice['days_overdue']),
                $invoice['total'], $invoice['paid'], $invoice['outstanding']], $card['open_invoices']),
            'total' => 'Total', 'sum' => [4, 5, 6], 'empty' => 'Tidak ada invoice yang belum lunas.',
        ], [
            'title' => "Kartu piutang {$ledger['from']} – {$ledger['to']}",
            'columns' => [['Tanggal', 13, 'date'], ['Jenis', 15], ['Nomor', 22], ['Keterangan', 30], ['Tagihan (+)', 15, 'money'],
                ['Pembayaran (−)', 15, 'money'], ['Saldo', 15, 'money']],
            'rows' => array_merge($rows, [[$ledger['to_at'], 'Saldo akhir', null, null, $ledger['debit'], $ledger['credit'], $ledger['closing']]]),
        ]])];
    }
}
