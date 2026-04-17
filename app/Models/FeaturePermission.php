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
     * User-level overrides take precedence over role defaults.
     */
    public static function hasAccess(User|int $user, string $featureKey): bool
    {
        $resolvedUser = $user instanceof User ? $user : User::find($user);

        if (!$resolvedUser) {
            return false;
        }

        // Superadmin always has full feature access.
        if ((bool) $resolvedUser->isSuperAdmin()) {
            return true;
        }

        $userId = $resolvedUser->id;
        
        // Check user-level permission first (overrides role)
        $userPermission = self::where('user_id', $userId)
            ->where('feature_key', $featureKey)
            ->first();

        if ($userPermission) {
            return $userPermission->enabled;
        }

        // Check role-level permission
        if (!$resolvedUser->role) {
            return false;
        }

        $roleId = self::ROLE_MAP[$resolvedUser->role] ?? null;

        if (!$roleId) {
            return false;
        }

        $rolePermission = self::where('role_id', $roleId)
            ->where('feature_key', $featureKey)
            ->first();

        // If no explicit permission set, check config defaults
        if (!$rolePermission) {
            $features = config('features.features', []);
            $feature = collect($features)->firstWhere('key', $featureKey);
            return $feature && in_array($resolvedUser->role, $feature['default_roles'] ?? []);
        }

        return $rolePermission->enabled;
    }

    public static function roleIdFor(string $role): ?int
    {
        return self::ROLE_MAP[$role] ?? null;
    }
}
