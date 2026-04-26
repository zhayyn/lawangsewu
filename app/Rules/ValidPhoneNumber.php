<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ValidPhoneNumber Validation Rule
 * 
 * Validates Indonesian phone numbers in various formats:
 * - 08xx-xxxx-xxxx (formatted)
 * - 08xxxxxxxxxx (unformatted)
 * - +62 8xx xxxx xxxx
 * 
 * Usage:
 * $validated = $request->validate([
 *     'phone' => ['required', 'string', new ValidPhoneNumber()],
 * ]);
 */
class ValidPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail("The {$attribute} must be a valid phone number.");
            return;
        }

        // Remove all non-digit characters except leading +
        $cleaned = preg_replace('/[^\d+]/', '', $value);

        // Check various valid formats
        $patterns = [
            '/^08\d{8,10}$/',        // 08xx-xxxx-xxxx or 08xxxxxxxxxx
            '/^\+628\d{8,10}$/',     // +62 8xx xxxx xxxx
            '/^628\d{8,10}$/',       // 628xxxxxxxxxx
        ];

        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleaned)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $fail("The {$attribute} field must be a valid Indonesian phone number.");
        }
    }
}
