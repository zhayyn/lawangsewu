<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Core\Traits\HasRolesAndPermissions;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRolesAndPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'alias',
        'email',
        'password',
        'google_id',
        'avatar',
        'is_active',
        'is_superadmin',
        'role',
    ];

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
            'is_superadmin' => 'boolean',
            'role' => 'string',
        ];
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

    public function hasAnyRole(array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($this->role, $roles, true);
    }
}
