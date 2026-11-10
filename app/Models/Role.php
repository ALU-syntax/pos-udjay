<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as ModelsRole;

class Role extends ModelsRole
{
    use HasFactory, SoftDeletes;

    /**
     * Role yang namanya dipakai langsung oleh kode: "super admin" untuk Gate::before
     * dan "kasir" untuk aturan satu outlet di form user. Tidak boleh diganti atau dihapus.
     */
    public const SYSTEM_ROLES = ['super admin', 'kasir'];

    public function isSystemRole(): bool
    {
        return in_array(strtolower($this->name), self::SYSTEM_ROLES, true);
    }
}
