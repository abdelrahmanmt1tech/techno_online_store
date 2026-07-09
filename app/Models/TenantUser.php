<?php

namespace App\Models;

use Database\Factories\TenantUserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class TenantUser extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<TenantUserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $connection = 'tenant';

    protected $table = 'users';

    protected $guard_name = 'tenant';

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'tenant';
    }
}
