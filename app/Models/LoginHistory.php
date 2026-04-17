<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'login_method',
        'logged_in_at',
        'logged_out_at',
        'session_id',
    ];

    protected $casts = [
        'logged_in_at'  => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── User-Agent Parsing ──────────────────────────

    /**
     * Create a login record from a Request object.
     */
    public static function recordLogin(
        int $userId,
        string $ipAddress,
        string $userAgent,
        string $loginMethod = 'google',
        ?string $sessionId = null,
    ): self {
        $parsed = self::parseUserAgent($userAgent);

        return self::create([
            'user_id'      => $userId,
            'ip_address'   => $ipAddress,
            'user_agent'   => $userAgent,
            'device_type'  => $parsed['device_type'],
            'browser'      => $parsed['browser'],
            'platform'     => $parsed['platform'],
            'login_method' => $loginMethod,
            'logged_in_at' => now(),
            'session_id'   => $sessionId,
        ]);
    }

    /**
     * Parse a user-agent string to extract device type, browser, and platform.
     *
     * @return array{device_type: string, browser: string, platform: string}
     */
    public static function parseUserAgent(string $ua): array
    {
        // Device type
        $deviceType = 'desktop';
        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $ua)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $ua)) {
            $deviceType = 'tablet';
        }

        // Browser
        $browser = 'Unknown';
        $browserPatterns = [
            '/Edg[ea]?\/[\d.]+/i'           => 'Edge',
            '/OPR\/[\d.]+|Opera\/[\d.]+/i'   => 'Opera',
            '/SamsungBrowser\/[\d.]+/i'       => 'Samsung Browser',
            '/Chrome\/[\d.]+/i'              => 'Chrome',
            '/Firefox\/[\d.]+/i'             => 'Firefox',
            '/Safari\/[\d.]+/i'              => 'Safari',
        ];

        foreach ($browserPatterns as $pattern => $name) {
            if (preg_match($pattern, $ua)) {
                $browser = $name;
                break;
            }
        }

        // Platform / OS
        $platform = 'Unknown';
        $platformPatterns = [
            '/Windows NT 10/i'   => 'Windows 10/11',
            '/Windows NT 6\.3/i' => 'Windows 8.1',
            '/Windows NT 6\.2/i' => 'Windows 8',
            '/Windows NT 6\.1/i' => 'Windows 7',
            '/Windows/i'         => 'Windows',
            '/Macintosh/i'       => 'macOS',
            '/iPhone/i'          => 'iOS',
            '/iPad/i'            => 'iPadOS',
            '/Android/i'         => 'Android',
            '/Linux/i'           => 'Linux',
            '/CrOS/i'            => 'ChromeOS',
        ];

        foreach ($platformPatterns as $pattern => $name) {
            if (preg_match($pattern, $ua)) {
                $platform = $name;
                break;
            }
        }

        return [
            'device_type' => $deviceType,
            'browser'     => $browser,
            'platform'    => $platform,
        ];
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeRecent($query, int $limit = 50)
    {
        return $query->latest('logged_in_at')->limit($limit);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Count active sessions (logged in but not logged out).
     */
    public function scopeActiveSessions($query)
    {
        return $query->whereNull('logged_out_at');
    }
}
