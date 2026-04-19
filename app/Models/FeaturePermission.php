<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturePermission extends Model
{
    public const ROLE_MAP = [
        'admin' => 1,
        'useradmin' => 2,
        'operator' => 3,
        'viewer' => 4,
    ];

    protected $fillable = ['role_id', 'user_id', 'feature_key', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a user has access to a feature.
     *
     * Role access is the maximum boundary. User-level overrides may only
     * restrict access within the role allowance and cannot expand beyond it.
     */
    public static function hasAccess(User|int $user, string $featureKey): bool
    {
        $resolvedUser = $user instanceof User ? $user : User::find($user);

        if (! $resolvedUser) {
            return false;
        }

        // Superadmin always has full feature access.
        if ((bool) $resolvedUser->isSuperAdmin()) {
            return true;
        }

        $roleAllowed = self::resolveRoleAccess($resolvedUser, $featureKey);

        // Role is the hard upper bound.
        if (! $roleAllowed) {
            return false;
        }

        $userId = $resolvedUser->id;

        // User override may only further restrict an already-allowed role feature.
        $userPermission = self::where('user_id', $userId)
            ->where('feature_key', $featureKey)
            ->first();

        if ($userPermission) {
            return (bool) $userPermission->enabled;
        }

        return true;
    }

    private static function resolveRoleAccess(User $user, string $featureKey): bool
    {
        if (! $user->role) {
            return false;
        }

        $roleId = self::ROLE_MAP[$user->role] ?? null;

        if (! $roleId) {
            return false;
        }

        $rolePermission = self::where('role_id', $roleId)
            ->where('feature_key', $featureKey)
            ->first();

        // If no explicit permission set, check config defaults
        if (! $rolePermission) {
            $features = config('features.features', []);
            $feature = collect($features)->firstWhere('key', $featureKey);
            return $feature && in_array($user->role, $feature['default_roles'] ?? [], true);
        }

        return (bool) $rolePermission->enabled;
    }

    public static function roleIdFor(string $role): ?int
    {
        return self::ROLE_MAP[$role] ?? null;
    }
}
