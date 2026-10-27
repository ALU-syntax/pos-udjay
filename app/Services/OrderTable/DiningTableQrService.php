<?php

namespace App\Services\OrderTable;

use App\Models\Config;
use App\Models\OrderTable\DiningTable;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

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
        return response($this->svg($table), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($table).'"',
        ]);
    }

    /**
     * ZIP berisi SVG QR, dikelompokkan per folder outlet agar mudah dibagikan ke operasional.
     *
     * @param  Collection<int, DiningTable>  $tables  sudah di-eager load relasi outlet
     */
    public function downloadZip(Collection $tables, string $zipName): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'qr-meja-');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file ZIP QR meja.');
        }

        foreach ($tables as $table) {
            $folder = $this->slug($table->outlet?->name ?? '') ?: 'outlet-'.$table->outlet_id;
            $zip->addFromString($folder.'/'.$this->filename($table), $this->svg($table));
        }

        $zip->close();

        return response()->download($path, $zipName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    private function filename(DiningTable $table): string
    {
        return 'table-'.($this->slug($table->code) ?: (string) $table->getKey()).'.svg';
    }

    public function slug(string $value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', $value), '-');
    }
}
