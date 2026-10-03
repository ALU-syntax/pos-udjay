<?php

namespace App\Services\OrderTable;

use App\Models\OrderTable\DiningTable;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DiningTableQrService
{
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function url(DiningTable $table): string
    {
        return rtrim((string) config('order-table.public_url'), '/').'/o/'.$table->outlet_id.'/t/'.$table->qr_token;
    }

    public function svg(DiningTable $table, int $size = 320): string
    {
        return (string) QrCode::format('svg')->size($size)->generate($this->url($table));
    }

    public function download(DiningTable $table): Response
    {
        $code = preg_replace('/[^A-Za-z0-9_-]+/', '-', $table->code) ?: (string) $table->getKey();
        $filename = 'table-'.$code.'.svg';

        return response($this->svg($table), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
