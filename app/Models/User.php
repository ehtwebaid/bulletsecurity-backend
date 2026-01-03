<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_status',
        'is_del',
        'profile_photo'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function staff()
    {
        return $this->hasOne(\App\Models\Staff::class, 'user_id');
    }

    public function favouriteData()
    {
        return $this->hasMany(\App\Models\Favourite::class, 'user_id')
                    ->where('is_favourite', 1);
    }
}
