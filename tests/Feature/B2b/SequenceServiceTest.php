<?php

namespace Tests\Feature\B2b;

use App\Models\Config;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\SequenceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SequenceServiceTest extends B2bTestCase
{
    /** Tanggal jauh di depan supaya tidak bentrok dengan sequence yang sudah ada di DB testing. */
    private function date(string $date): Carbon
    {
        return Carbon::parse($date);
    }

    public function test_invoice_number_uses_two_digits_and_resets_every_month(): void
    {
        Config::query()->where('name', B2bSettings::INVOICE_NUMBER_CODE_KEY)->delete();
        $sequences = app(SequenceService::class);

        $this->assertSame('01/INV/BGR/03/2031', $sequences->invoiceNumber($this->date('2031-03-05')));
        $this->assertSame('02/INV/BGR/03/2031', $sequences->invoiceNumber($this->date('2031-03-28')));
        // Bulan baru mulai lagi dari 01.
        $this->assertSame('01/INV/BGR/04/2031', $sequences->invoiceNumber($this->date('2031-04-01')));

        // Lebih dari 99 dalam sebulan tetap berurutan.
        DB::table('b2b_sequences')->where('type', 'invoice')->where('period', '203104')->update(['last_number' => 99]);
        $this->assertSame('100/INV/BGR/04/2031', $sequences->invoiceNumber($this->date('2031-04-30')));
    }

    public function test_invoice_number_code_comes_from_config(): void
    {
        Config::setValue(B2bSettings::INVOICE_NUMBER_CODE_KEY, ' jkt ', null, 'string');
        $this->assertSame('01/INV/JKT/05/2031', app(SequenceService::class)->invoiceNumber($this->date('2031-05-10')));

        // Isi tidak valid (mis. ada garis miring) kembali ke kode cadangan.
        Config::setValue(B2bSettings::INVOICE_NUMBER_CODE_KEY, 'BG/R', null, 'string');
        $this->assertSame('02/INV/BGR/05/2031', app(SequenceService::class)->invoiceNumber($this->date('2031-05-11')));
    }
}
