<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtendSanctumTokenExpiration
{
    /**
     * Perpanjang masa berlaku token Sanctum secara otomatis (sliding expiration).
     *
     * Setiap request terautentikasi akan menggeser tanggal kedaluwarsa token
     * ke depan selama `ttlHours`. Untuk menghindari penulisan DB tiap request,
     * token hanya ditulis ulang saat sisa masa berlaku kurang dari separuh TTL.
     *
     * Device yang aktif dipakai tidak akan pernah kedaluwarsa. Device yang
     * tidak dipakai melebihi TTL akan kedaluwarsa dan wajib login ulang.
     *
     * @param  int  $ttlHours  Masa berlaku token (jam), mengikuti default Sanctum.
     */
    public function handle(Request $request, Closure $next, int $ttlHours = 24): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token && $token->expires_at) {
            $remainingHours = $token->expires_at->diffInHours(now(), false);

            // Auth sudah lolos sehingga token pasti belum kedaluwarsa.
            // Perpanjang hanya saat sisa hidup < 50% dari TTL.
            if ($remainingHours !== false && $remainingHours < ($ttlHours / 2)) {
                $token->forceFill(['expires_at' => now()->addHours($ttlHours)])->save();
            }
        }

        return $next($request);
    }
}