<?php

namespace App\Jobs;

use App\Imports\BackupImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessBackupImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        protected string $filePath,
        protected string $importId,
        protected ?int $userId,
        protected ?int $fallbackOutletId,
    ) {}

    public function handle(): void
    {
        $absolutePath = Storage::path($this->filePath);

        Cache::put(BackupImport::progressCacheKey($this->userId, $this->importId), array_merge(BackupImport::defaultProgress(), [
            'status' => 'processing',
            'message' => 'Import mulai diproses.',
        ]), now()->addHour());

        try {
            (new BackupImport($this->importId, $this->userId, $this->fallbackOutletId))->importPath($absolutePath);
        } finally {
            Storage::delete($this->filePath);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Import history MOKA gagal: ' . ($exception?->getMessage() ?? 'Unknown error'));

        Cache::put(BackupImport::progressCacheKey($this->userId, $this->importId), array_merge(BackupImport::defaultProgress(), [
            'status' => 'failed',
            'message' => 'Import gagal.',
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]), now()->addHour());

        Storage::delete($this->filePath);
    }
}
