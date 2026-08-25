<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlets;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Get all outlets for kasir login screen.
     *
     * GET /api/v1/outlets
     */
    public function outlets(): JsonResponse
    {
        $outlets = Outlets::orderBy('name')
            ->get(['id', 'name', 'address', 'phone']);

        return response()->json([
            'status' => 'success',
            'data'   => $outlets,
        ]);
    }

    /**
     * Get all active kasir users (role = 3) assigned to a specific outlet.
     *
     * outlet_id is double-encoded in DB e.g. raw value: "[\"3\"]"
     * Data is stored as strings inside the JSON array, so we use JSON_QUOTE
     * to match the string representation of the outlet ID.
     *
     * GET /api/v1/outlets/{outletId}/users
     */
    public function usersByOutlet(int $outletId): JsonResponse
    {
        $outlet = Outlets::find($outletId);

        if (!$outlet) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Outlet tidak ditemukan',
            ], 404);
        }

        $users = User::whereRaw('JSON_CONTAINS(JSON_UNQUOTE(outlet_id), JSON_QUOTE(?))', [(string) $outletId])
            ->where('role', 3)
            ->where('status', 1)
            ->where('deleted', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        return response()->json([
            'status' => 'success',
            'data'   => $users,
        ]);
    }

    /**
     * Login kasir with username + PIN, returns Sanctum token.
     *
     * Validasi:
     * - username harus ada dan role = 3 (kasir)
     * - PIN 6 digit harus cocok dengan hash di DB
     * - outlet_id harus cocok dengan outlet yang dimiliki user
     *
     * Rate limiting: max 5 percobaan per username + IP
     *
     * POST /api/v1/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username'  => ['required', 'string'],
            'pin'       => ['required', 'string', 'size:6'],
            'outlet_id' => ['required', 'integer'],
        ]);

        // Rate limiting: max 5 attempts per username + IP
        $throttleKey = Str::lower($request->username) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . $seconds . ' detik.',
            ], 429);
        }

        // Find kasir user by username
        $user = User::where('username', $request->username)
            ->where('role', 3)
            ->where('status', 1)
            ->where('deleted', 0)
            ->first();

        // Verify: user exists, PIN matches, and outlet matches
        if (
            !$user ||
            !$user->pin ||
            !Hash::check($request->pin, $user->pin) ||
            !in_array($request->outlet_id, $user->outletIds())
        ) {
            RateLimiter::hit($throttleKey);

            return response()->json([
                'status'  => 'error',
                'message' => 'Username, PIN, atau outlet tidak valid.',
            ], 401);
        }

        // Clear rate limiter on success
        RateLimiter::clear($throttleKey);

        // Revoke previous android-kasir tokens to keep only 1 active token
        $user->tokens()->where('name', 'android-kasir')->delete();

        // Generate new Sanctum token — expires in 24 hours
        // expires_at is stored in personal_access_tokens.expires_at
        // Expiry is checked in PHP by Sanctum on every request, not by DB TTL
        $token = $user->createToken(
            'android-kasir',
            ['*'],
            now()->addHours(24)
        )->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'token' => $token,
                'user'  => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'username'  => $user->username,
                    'outlet_id' => $user->outletIds()[0] ?? null,
                ],
            ],
        ]);
    }

    /**
     * Logout kasir, revoke current Sanctum token.
     *
     * POST /api/v1/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil.',
        ]);
    }
}
