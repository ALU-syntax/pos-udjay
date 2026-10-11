<?php

namespace App\Services\OrderTable;

use App\Models\Config;
use App\Models\OrderTable\DiningTable;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DiningTableQrService
{
    public const PUBLIC_URL_CONFIG_KEY = 'order_table_public_url';

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Base URL halaman order customer.
     *
     * Prioritas: value dari tabel `configs` (bisa diubah runtime dari UI).
     * Bila row config belum pernah dibuat, fallback ke domain backoffice
     * (`config('app.url')`) supaya QR tetap bisa dibuat tanpa error.
     */
    public static function resolveBaseUrl(): string
    {
        $fromConfig = Config::getValue(self::PUBLIC_URL_CONFIG_KEY);
        $value = is_string($fromConfig) && trim($fromConfig) !== ''
            ? $fromConfig
            : config('app.url');

        return rtrim((string) $value, '/');
    }

    public function url(DiningTable $table): string
    {
        return static::resolveBaseUrl().'/o/'.$table->outlet_id.'/t/'.$table->qr_token;
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
