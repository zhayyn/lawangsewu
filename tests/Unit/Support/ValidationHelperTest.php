<?php

namespace Tests\Unit\Support;

use App\Support\ValidationHelper;
use PHPUnit\Framework\TestCase;

class ValidationHelperTest extends TestCase
{
    public function test_sanitize_removes_script_tags(): void
    {
        $input = '<script>alert(1)</script>Hello';
        $result = ValidationHelper::sanitize($input);
        
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function test_sanitize_removes_onclick_handler(): void
    {
        $input = '<div onclick="alert(1)">Click me</div>';
        $result = ValidationHelper::sanitize($input);
        
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function test_sanitize_removes_null_bytes(): void
    {
        $input = "Hello\x00World";
        $result = ValidationHelper::sanitize($input);
        
        $this->assertStringNotContainsString("\x00", $result);
    }

    public function test_sanitize_preserves_safe_text(): void
    {
        $input = 'This is safe text with numbers 123 and special chars &!';
        $result = ValidationHelper::sanitize($input);
        
        $this->assertEquals($input, $result);
    }

    public function test_has_dangerous_content_detects_script_tag(): void
    {
        $this->assertTrue(ValidationHelper::hasDangerousContent('<script>alert(1)</script>'));
    }

    public function test_has_dangerous_content_detects_javascript_protocol(): void
    {
        $this->assertTrue(ValidationHelper::hasDangerousContent('javascript:alert(1)'));
    }

    public function test_has_dangerous_content_detects_iframe(): void
    {
        $this->assertTrue(ValidationHelper::hasDangerousContent('<iframe src="http://evil.com"></iframe>'));
    }

    public function test_has_dangerous_content_returns_false_for_safe_content(): void
    {
        $this->assertFalse(ValidationHelper::hasDangerousContent('This is safe text'));
    }

    public function test_is_valid_json_accepts_valid_json(): void
    {
        $json = '{"name":"John","age":30}';
        $this->assertTrue(ValidationHelper::isValidJson($json));
    }

    public function test_is_valid_json_rejects_invalid_json(): void
    {
        $invalid = '{"name":"John"incomplete}';
        $this->assertFalse(ValidationHelper::isValidJson($invalid));
    }

    public function test_is_valid_json_rejects_non_string(): void
    {
        $this->assertFalse(ValidationHelper::isValidJson(['array']));
    }

    public function test_sanitize_array_sanitizes_all_values(): void
    {
        $data = [
            'name' => '<script>John</script>',
            'email' => 'john@example.com',
            'nested' => [
                'message' => '<div onclick="alert(1)">Text</div>',
            ],
        ];

        $result = ValidationHelper::sanitizeArray($data);

        $this->assertStringNotContainsString('<script>', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertStringNotContainsString('onclick', $result['nested']['message']);
    }

    public function test_is_valid_email_accepts_valid_email(): void
    {
        $this->assertTrue(ValidationHelper::isValidEmail('user@example.com'));
        $this->assertTrue(ValidationHelper::isValidEmail('john.doe+tag@company.co.uk'));
    }

    public function test_is_valid_email_rejects_invalid_email(): void
    {
        $this->assertFalse(ValidationHelper::isValidEmail('not-an-email'));
        $this->assertFalse(ValidationHelper::isValidEmail('user@'));
        $this->assertFalse(ValidationHelper::isValidEmail('@example.com'));
    }

    public function test_is_valid_url_accepts_valid_urls(): void
    {
        $this->assertTrue(ValidationHelper::isValidUrl('http://example.com'));
        $this->assertTrue(ValidationHelper::isValidUrl('https://example.com/path'));
        $this->assertTrue(ValidationHelper::isValidUrl('ftp://files.example.com'));
    }

    public function test_is_valid_url_rejects_invalid_urls(): void
    {
        $this->assertFalse(ValidationHelper::isValidUrl('not a url'));
        $this->assertFalse(ValidationHelper::isValidUrl('example.com'));
    }

    public function test_sanitize_url_rejects_dangerous_schemes(): void
    {
        $this->assertNull(ValidationHelper::sanitizeUrl('javascript:alert(1)'));
        $this->assertNull(ValidationHelper::sanitizeUrl('data:text/html,<script>alert(1)</script>'));
        $this->assertNull(ValidationHelper::sanitizeUrl('vbscript:msgbox(1)'));
    }

    public function test_sanitize_url_accepts_safe_urls(): void
    {
        $url = 'https://example.com/path';
        $this->assertEquals($url, ValidationHelper::sanitizeUrl($url));
    }

    public function test_truncate_shortens_long_string(): void
    {
        $long = 'This is a very long string that needs to be truncated';
        $result = ValidationHelper::truncate($long, 20);
        
        $this->assertEquals('This is a very long ...', $result);
    }

    public function test_truncate_preserves_short_string(): void
    {
        $short = 'Short text';
        $result = ValidationHelper::truncate($short, 50);
        
        $this->assertEquals($short, $result);
    }

    public function test_truncate_uses_custom_end(): void
    {
        $long = 'This is a very long string';
        $result = ValidationHelper::truncate($long, 10, '→');
        
        $this->assertEquals('This is a →', $result);
    }
}
