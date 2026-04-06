<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_nip',
        'year',
        'annual_quota',
        'used',
        'remaining',
        'carryover_from_previous',
        'notes',
        'source_system',
        'source_payload',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'annual_quota' => 'integer',
            'used' => 'integer',
            'remaining' => 'integer',
            'carryover_from_previous' => 'integer',
            'source_payload' => 'array',
            'last_updated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_nip', 'nip');
    }

    /**
     * Deduct leave days from balance
     */
    public function deduct(int $days): void
    {
        $this->used += $days;
        $this->remaining -= $days;
        $this->save();
    }

    /**
     * Restore leave days to balance
     */
    public function restore(int $days): void
    {
        $this->used -= $days;
        $this->remaining += $days;
        $this->save();
    }

    /**
     * Get or create balance for current year
     */
    public static function getCurrentYearBalance(string $employeeNip, int $currentYear = null): self
    {
        $year = $currentYear ?? now()->year;
        
        return self::firstOrCreate(
            ['employee_nip' => $employeeNip, 'year' => $year],
            [
                'annual_quota' => 12,
                'used' => 0,
                'remaining' => 12,
                'source_system' => 'jatidiri-native',
            ]
        );
    }
}
