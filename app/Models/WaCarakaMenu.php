<?php

namespace App\Models;

/**
 * WaCarakaMenu
 *
 * Chatbot menu entries for the WA Caraka auto-reply system.
 * Migrated from wamehehe's `format` table.
 *
 * Types:
 *   - direct: reply immediately with response_text
 *   - input:  ask for input (prompt_text), then process response_query with user input
 *   - prompt: show a sub-menu (response_text = list of sub-options)
 */
class WaCarakaMenu extends WaCarakaModel
{
    protected $fillable = [
        'command',
        'label',
        'type',
        'response_text',
        'response_query',
        'sipp_query_type',       // e.g. 'status_perkara', 'jadwal_sidang', etc.
        'prompt_text',
        'extra_text',
        'description',
        'creates_ticket_type',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCommand($query, string $command)
    {
        return $query->where('command', $command);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    public function requiresInput(): bool
    {
        return $this->type === 'input';
    }

    public function isPrompt(): bool
    {
        return $this->type === 'prompt';
    }

    public function createsTicket(): bool
    {
        return !empty($this->creates_ticket_type);
    }

    /**
     * Find an active menu by its command string.
     */
    public static function findByCommand(string $command): ?self
    {
        return static::active()->byCommand($command)->first();
    }
}
