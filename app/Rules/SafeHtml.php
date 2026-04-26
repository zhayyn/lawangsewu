<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * SafeHtml Validation Rule
 * 
 * Prevents XSS attacks by rejecting input containing:
 * - JavaScript event handlers (onclick, onerror, onload, etc.)
 * - Script tags
 * - Dangerous protocol schemes (javascript:, data:, etc.)
 * 
 * Usage:
 * $validated = $request->validate([
 *     'message' => ['required', 'string', 'max:1000', new SafeHtml()],
 *     'comment' => ['required', 'string', new SafeHtml()],
 * ]);
 */
class SafeHtml implements ValidationRule
{
    private array $dangerousPatterns = [
        // JavaScript event handlers
        '/on\w+\s*=/i',           // onclick=, onerror=, etc.
        
        // Script tags
        '/<script[\s\S]*?<\/script>/i',
        
        // Iframe and other dangerous tags
        '/<iframe/i',
        '/<embed/i',
        '/<object/i',
        
        // Dangerous attributes
        '/javascript:/i',
        '/data:text\/html/i',
        '/vbscript:/i',
        
        // HTML entities that could bypass filters
        '/&#x3c;script/i',
        '/&lt;script/i',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail("The {$attribute} field contains invalid or dangerous content.");
                return;
            }
        }
    }
}
