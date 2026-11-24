<?php

namespace App\Support\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\Order;

/**
 * Data turunan untuk dokumen invoice (cetak browser dan PDF lampiran reminder), supaya dua template memakai hitungan yang sama.
 * Invoice perlu dimuat dengan order.items, customer, allocations.payment.
 */
final class InvoiceDocument
{
    /**
     * @return array{order: Order, immediate: bool, void: bool, paid: bool, payments: \Illuminate\Support\Collection,
     *               termDays: int, lines: \Illuminate\Support\Collection, showBalance: bool, emptyLines: int}
     */
    public static function data(Invoice $invoice): array
    {
        $order = $invoice->order;
        $paid = $invoice->status === Invoice::STATUS_PAID;
        $void = $invoice->status === Invoice::STATUS_VOID;

        // Baris tagihan: item pesanan, lalu ongkir dan kode unik kalau ada.
        $lines = $order->items->map(fn ($item) => [
            'description' => $item->displayName(),
            'note' => $item->notes,
            'price' => $item->final_price,
            'qty' => $item->qty,
            'total' => $item->line_total,
        ]);
        if ($order->shipping_cost) {
            $lines->push(['description' => 'Ongkir', 'note' => null, 'price' => $order->shipping_cost, 'qty' => 1, 'total' => $order->shipping_cost]);
        }
        if ($invoice->unique_code_amount) {
            $lines->push(['description' => 'Unique code', 'note' => null, 'price' => $invoice->unique_code_amount, 'qty' => 1, 'total' => $invoice->unique_code_amount]);
        }
        $showBalance = $invoice->paid_amount && ! $paid && ! $void;

        return [
            'order' => $order,
            'immediate' => $order->payment_type === Order::PAYMENT_IMMEDIATE,
            'void' => $void,
            'paid' => $paid,
            'payments' => $invoice->allocations->pluck('payment')->filter()->where('status', 'posted')->unique('id')->values(),
            'termDays' => (int) $invoice->issue_date->diffInDays($invoice->due_date),
            'lines' => $lines,
            'showBalance' => (bool) $showBalance,
            // Baris kosong pengisi seperti template; dikurangi kalau ada baris Paid/Balance supaya tetap satu halaman.
            'emptyLines' => max(0, ($showBalance ? 2 : 3) - $lines->count()),
        ];
    }

    /** Nomor kwitansi mengikuti nomor invoice: 07/INV/BGR/11/2026 → 07/KWI/BGR/11/2026 (tanpa penomoran terpisah). */
    public static function receiptNumber(Invoice $invoice): string
    {
        return str_replace('/INV/', '/KWI/', $invoice->invoice_number);
    }

    /** Nama file PDF kwitansi, mis. "Kwitansi 07-KWI-BGR-11-2026 - PT Contoh.pdf". */
    public static function receiptPdfFilename(Invoice $invoice): string
    {
        $number = str_replace('/', '-', self::receiptNumber($invoice));
        $customer = preg_replace('/[^\pL\pN ._-]+/u', '', (string) $invoice->customer?->name);

        return trim('Kwitansi '.$number.($customer !== '' ? ' - '.$customer : '')).'.pdf';
    }

    /** Nama file PDF, mis. "Invoice 07-INV-BGR-11-2026 - PT Contoh.pdf". */
    public static function pdfFilename(Invoice $invoice): string
    {
        $number = str_replace('/', '-', $invoice->invoice_number);
        $customer = preg_replace('/[^\pL\pN ._-]+/u', '', (string) $invoice->customer?->name);

        return trim('Invoice '.$number.($customer !== '' ? ' - '.$customer : '')).'.pdf';
    }
}
