<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class PruneBirthdayEmailLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:birthday-log-prune {--days=30 : Jumlah hari log yang dipertahankan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus isi log email ulang tahun yang lebih tua dari 30 hari';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = config('logging.channels.birthday.path');
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('--days harus lebih dari 0.');

            return self::FAILURE;
        }

        if (! is_file($path)) {
            $this->info('Belum ada file log email ulang tahun.');

            return self::SUCCESS;
        }

        $cutoff = Carbon::today()->subDays($days)->toDateString();

        // File ditulis ulang di tempat (bukan diganti file baru) supaya proses
        // yang sedang memegang file ini, mis. queue worker, tetap menulis ke file yang sama.
        $handle = fopen($path, 'c+');
        flock($handle, LOCK_EX);

        $kept = '';
        $keep = true;
        $removed = 0;

        while (($line = fgets($handle)) !== false) {
            // Baris tanpa tanggal (lanjutan stack trace) mengikuti baris bertanggal di atasnya.
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2})[ T]/', $line, $match)) {
                $keep = $match[1] >= $cutoff;
            }

            if ($keep) {
                $kept .= $line;
            } else {
                $removed++;
            }
        }

        if ($removed > 0) {
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $kept);
            fflush($handle);
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        $this->info("Menghapus {$removed} baris log sebelum {$cutoff}.");

        return self::SUCCESS;
    }
}
