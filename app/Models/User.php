<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

     const STATUS = [
        '0'=>'Non Aktif',
        '1'=>'Aktif', 
    ];

    protected $guarded = ['id'];
    protected $fillable = [
        'name',
        'username',
        'email',
        'partai',
        'password',
        'status',
        'role',
        'kelurahan_id',
        'role2',
        'deleted',
        'outlet_id',
        'pin'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'outlet_id' => 'array'
    ];

    public function outlets()
    {
        return $this->belongsToMany('outlet_id', Outlets::class, 'id');
    }

}
