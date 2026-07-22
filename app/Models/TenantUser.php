<?php

namespace App\Models;

use Database\Factories\TenantUserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Models\Tenant\Favorite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class TenantUser extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<TenantUserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $connection = 'tenant';

    protected $table = 'users';

    protected $guard_name = 'tenant';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'email_verified_at',
        'is_admin',
        'verification_code',
        'verification_code_expires_at',
        'is_verified',
        'reset_password_token',
        'reset_password_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'reset_password_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_verified' => 'boolean',
            'verification_code_expires_at' => 'datetime',
            'reset_password_token_expires_at' => 'datetime',
        ];
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'tenant' && $this->is_admin;
    }
}
