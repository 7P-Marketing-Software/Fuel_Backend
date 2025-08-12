<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Http\Traits\ArchiveTrait;
use App\Http\Traits\StatisticsTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements \Illuminate\Contracts\Auth\Access\Authorizable
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, SoftDeletes, ArchiveTrait, StatisticsTrait;

    protected $guard_name = 'web';
    protected $fillable = [
        'name',
        'email',
        'country_code',
        'phone',
        'password',
        'profile_image',
        'gender',
        'otp',
        'otp_sent_at',
        'otp_verified_at',
        'otp_expires_at',
        'otp_attempts',
        'last_active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'otp_sent_at' => 'datetime',
        'otp_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'last_active_at' => 'datetime',
    ];
}
