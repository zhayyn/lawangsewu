<?php

namespace App\Support;

use App\Rules\SafeHtml;

/**
 * Input Validation & Sanitization Helper
 * 
 * Provides utilities for validating and sanitizing user input.
 * Helps prevent XSS, injection attacks, and data corruption.
 * 
 * Usage:
 * $safe = ValidationHelper::sanitize($input);
 * if (!ValidationHelper::isValidJson($data)) { }
 */
class ValidationHelper
{
    /**
     * Sanitize string input by removing dangerous content
     * while preserving legitimate HTML if safe.
     * 
     * @param string $input
     * @return string
     */
    public static function sanitize(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", "", $input);
        
        // Decode HTML entities to check content
        $decoded = html_entity_decode($input, ENT_QUOTES | ENT_HTML5);
        
        // Check for dangerous patterns
        if (self::hasDangerousContent($decoded)) {
            // Strip all HTML tags if dangerous
            return strip_tags($input);
        }
        
        // Use HTML Purifier-like approach: allow specific safe tags
        return self::allowSafeTags($input);
    }

    /**
     * Check if input contains dangerous content
     * 
     * @param string $input
     * @return bool
     */
    public static function hasDangerousContent(string $input): bool
    {
        $dangerousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/data:text\/html/i',
            '/<iframe/i',
            '/<embed/i',
            '/vbscript:/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Allow only safe HTML tags
     * 
     * @param string $input
     * @return string
     */
    public static function allowSafeTags(string $input): string
    {
        // Allow basic formatting tags only
        $allowedTags = '<b><i><u><br><p><a><ul><ol><li><strong><em>';
        return strip_tags($input, $allowedTags);
    }

    /**
     * Validate JSON data
     * 
     * @param mixed $data
     * @return bool
     */
    public static function isValidJson($data): bool
    {
        if (is_string($data)) {
            json_decode($data);
            return (json_last_error() === JSON_ERROR_NONE);
        }

        return false;
    }

    /**
     * Sanitize array recursively
     * 
     * @param array $data
     * @return array
     */
    public static function sanitizeArray(array $data): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return self::sanitizeArray($value);
            }

            if (is_string($value)) {
                return self::sanitize($value);
            }

            return $value;
        }, $data);
    }

    /**
     * Validate email format
     * 
     * @param string $email
     * @return bool
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL format
     * 
     * @param string $url
     * @return bool
     */
    public static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Sanitize URL (remove dangerous schemes)
     * 
     * @param string $url
     * @return string|null
     */
    public static function sanitizeUrl(string $url): ?string
    {
        $url = trim($url);

        // Reject dangerous schemes
        if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return null;
        }

        if (self::isValidUrl($url)) {
            return $url;
        }

        return null;
    }

    /**
     * Limit string length with ellipsis
     * 
     * @param string $string
     * @param int $length
     * @param string $end
     * @return string
     */
    public static function truncate(string $string, int $length = 100, string $end = '...'): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        return substr($string, 0, $length) . $end;
    }
}
