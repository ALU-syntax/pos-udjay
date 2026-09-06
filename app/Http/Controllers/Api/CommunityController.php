<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Community;
use Illuminate\Http\JsonResponse;

class CommunityController extends Controller
{
    /**
     * Daftar komunitas untuk dropdown community di form "Tambah Member".
     *
     * GET /api/v1/communities
     */
    public function index(): JsonResponse
    {
        $communities = Community::where('status', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return response()->json([
            'status' => 'success',
            'data'   => $communities,
        ]);
    }
}