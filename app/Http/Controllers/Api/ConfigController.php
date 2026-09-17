<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Ambil config aplikasi untuk kebutuhan mobile.
     *
     * GET /api/v1/configs
     * GET /api/v1/configs?name=password_min_length
     * GET /api/v1/configs?name=password_min_length,pin_length
     *
     * Tanpa parameter name  -> mengembalikan seluruh config (untuk sync ke Room).
     * Dengan parameter name -> hanya config yang namanya diminta.
     *
     * Nilai `value` sudah di-cast sesuai kolom `type` (integer, float, boolean,
     * json, string) sehingga mobile tidak perlu menebak tipe datanya.
     *
     * Jika `name` diisi tetapi ada nama yang tidak ditemukan, nama tersebut
     * dikembalikan pada `missing` agar client bisa tahu config mana yang stale.
     */
    public function index(Request $request): JsonResponse
    {
        $requestedNames = $this->requestedNames($request);

        $query = Config::query();

        if ($requestedNames !== null) {
            $query->whereIn('name', $requestedNames);
        }

        $configs = $query->orderBy('name', 'asc')->get();

        $missing = $requestedNames === null
            ? []
            : array_values(array_diff($requestedNames, $configs->pluck('name')->all()));

        return response()->json([
            'status' => 'success',
            'data' => $configs->map(fn (Config $config) => $config->toApiArray())->values(),
            'missing' => $missing,
        ]);
    }

    /**
     * Ambil config berdasarkan name.
     *
     * GET /api/v1/configs/{name}
     *
     * Nilai `value` sudah di-cast sesuai `type`.
     */
    public function show(string $name): JsonResponse
    {
        $config = Config::where('name', $name)->first();

        if (! $config) {
            return response()->json([
                'status' => 'error',
                'message' => "Config '{$name}' tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $config->toApiArray(),
        ]);
    }

    /**
     * Parse query param `name` (mendukung pemisah koma untuk multiple name).
     *
     * @return array<int, string>|null null jika param tidak dikirim
     */
    protected function requestedNames(Request $request): ?array
    {
        $raw = $request->query('name');

        if ($raw === null || $raw === '') {
            return null;
        }

        $names = is_array($raw) ? $raw : explode(',', (string) $raw);

        $names = array_map('trim', $names);
        $names = array_filter($names, fn ($name) => $name !== '');

        return $names === [] ? null : array_values(array_unique($names));
    }
}
