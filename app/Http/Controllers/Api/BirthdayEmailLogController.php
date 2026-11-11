<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BirthdayEmailLogController extends Controller
{
    /**
     * Ringkasan log email ulang tahun untuk satu tanggal (default: kemarin, WIB).
     *
     * GET /api/v1/birthday-email-log
     * GET /api/v1/birthday-email-log?date=2026-11-12
     *
     * Endpoint ini publik (dibatasi rate limiter "birthday-log"), jadi hanya
     * mengembalikan ringkasan: isi mentah log dan pesan error tidak ikut dikirim.
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? Carbon::yesterday()->toDateString();
        $path = config('logging.channels.birthday.path');

        $summary = [
            'tanggal' => $date,
            'log_tersedia' => is_file($path),
            'jumlah_putaran' => 0,
            'member_diproses' => 0,
            'terkirim' => 0,
            'gagal' => 0,
            'putaran' => [],
            'customer_id_gagal' => [],
        ];

        if ($summary['log_tersedia']) {
            $prefix = '[' . $date . ' ';

            foreach (new \SplFileObject($path) as $line) {
                if (! is_string($line) || ! str_starts_with($line, $prefix)) {
                    continue;
                }

                $context = $this->context($line);

                // Teks pesan di bawah harus sama dengan yang ditulis SendBirthdayEmail
                // (command) dan SendBirthdayEmailJob.
                if (str_contains($line, 'email:birthday dijalankan')) {
                    $jumlah = (int) ($context['jumlah_member'] ?? 0);
                    $summary['jumlah_putaran']++;
                    $summary['member_diproses'] += $jumlah;
                    $summary['putaran'][] = [
                        'waktu' => substr($line, 12, 8),
                        'jumlah_member' => $jumlah,
                    ];
                } elseif (str_contains($line, 'Email ulang tahun terkirim')) {
                    $summary['terkirim']++;
                } elseif (str_contains($line, 'Email ulang tahun gagal')) {
                    $summary['gagal']++;

                    if (isset($context['customer_id'])) {
                        $summary['customer_id_gagal'][] = $context['customer_id'];
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $summary,
        ]);
    }

    /**
     * Ambil konteks JSON di akhir baris log, mis. {"customer_id":123}.
     */
    private function context(string $line): array
    {
        $start = strpos($line, '{');
        $end = strrpos($line, '}');

        if ($start === false || $end === false || $end < $start) {
            return [];
        }

        $context = json_decode(substr($line, $start, $end - $start + 1), true);

        return is_array($context) ? $context : [];
    }
}
