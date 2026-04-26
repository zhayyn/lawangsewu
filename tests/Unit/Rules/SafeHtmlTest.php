<?php

namespace Tests\Unit\Rules;

use App\Rules\SafeHtml;
use PHPUnit\Framework\TestCase;

class SafeHtmlTest extends TestCase
{
    private SafeHtml $rule;

    protected function setUp(): void
    {
        $this->rule = new SafeHtml();
    }

    public function test_rejects_script_tags(): void
    {
        $passed = true;
        $this->rule->validate('field', '<script>alert(1)</script>', 
            fn() => $passed = false
        );
        
        $this->assertFalse($passed);
    }

    public function test_rejects_javascript_protocol(): void
    {
        $passed = true;
        $this->rule->validate('field', '<a href="javascript:alert(1)">click</a>', 
            fn() => $passed = false
        );
        
        $this->assertFalse($passed);
    }

    public function test_rejects_data_uri(): void
    {
        $passed = true;
        $this->rule->validate('field', '<img src="data:text/html,<script>alert(1)</script>">', 
            fn() => $passed = false
        );
        
        $this->assertFalse($passed);
    }

    public function test_rejects_onclick_handler(): void
    {
        $passed = true;
        $this->rule->validate('field', '<div onclick="alert(1)">click</div>', 
            fn() => $passed = false
        );
        
        $this->assertFalse($passed);
    }

    public function test_rejects_onerror_handler(): void
    {
        $passed = true;
        $this->rule->validate('field', '<img src="x" onerror="alert(1)">', 
            fn() => $passed = false
        );
        
        $this->assertFalse($passed);
    }

    public function test_rejects_iframe_tag(): void
    {
        $passed = true;
        $this->rule->validate('field', '<iframe src="http://evil.com"></iframe>', 
            fn() => $passed = false
        );
        
        $this->assertFalse($passed);
    }

    public function test_accepts_safe_html(): void
    {
        $passed = true;
        $this->rule->validate('field', 'This is <b>bold</b> text', 
            fn() => $passed = false
        );
        
        $this->assertTrue($passed);
    }

    public function test_accepts_plain_text(): void
    {
        $passed = true;
        $this->rule->validate('field', 'This is plain text', 
            fn() => $passed = false
        );
        
        $this->assertTrue($passed);
    }

    public function test_accepts_text_with_special_chars(): void
    {
        $passed = true;
        $this->rule->validate('field', 'Email: user@example.com & phone: 08xxxxxxxxxx', 
            fn() => $passed = false
        );
        
        $this->assertTrue($passed);
    }

    public function test_rejects_encoded_script_tag(): void
    {
        $passed = true;
        $this->rule->validate('field', '&#x3c;script&#x3e;alert(1)&#x3c;/script&#x3e;', 
            fn() => $passed = false
        );
        
        $this->assertFalse($passed);
    }

    public function test_ignores_non_string_values(): void
    {
        $passed = true;
        
        // Should not fail for non-string values
        $this->rule->validate('field', 123, fn() => $passed = false);
        $this->rule->validate('field', [], fn() => $passed = false);
        $this->rule->validate('field', null, fn() => $passed = false);
        
        $this->assertTrue($passed);
    }
}
