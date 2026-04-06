<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Core\Traits\HasRolesAndPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Authorizable, HasFactory, HasRolesAndPermissions, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nip',
        'alias',
        'email',
        'password',
        'google_id',
        'avatar',
        'is_active',
        'role',
        'is_superadmin',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->alias ?: $this->name;
    }

    public function isSuperAdmin(): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        $configuredEmail = strtolower((string) config('auth.super_admin_email'));

        return $configuredEmail !== ''
            && strtolower((string) $this->email) === $configuredEmail;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => 'string',
            'is_superadmin' => 'boolean',
        ];
    }

    public function hasAnyRole(array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($this->role, $roles, true);
    }
}
