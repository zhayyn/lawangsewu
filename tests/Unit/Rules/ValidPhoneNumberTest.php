<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidPhoneNumber;
use PHPUnit\Framework\TestCase;

class ValidPhoneNumberTest extends TestCase
{
    private ValidPhoneNumber $rule;

    protected function setUp(): void
    {
        $this->rule = new ValidPhoneNumber();
    }

    public function test_accepts_standard_indonesian_format(): void
    {
        $passed = true;
        $this->rule->validate('phone', '08123456789', fn() => $passed = false);
        $this->assertTrue($passed);
    }

    public function test_accepts_formatted_indonesian_number(): void
    {
        $passed = true;
        $this->rule->validate('phone', '0812-3456-789', fn() => $passed = false);
        $this->assertTrue($passed);
    }

    public function test_accepts_international_format_with_plus(): void
    {
        $passed = true;
        $this->rule->validate('phone', '+62 812 3456 789', fn() => $passed = false);
        $this->assertTrue($passed);
    }

    public function test_accepts_international_format_without_plus(): void
    {
        $passed = true;
        $this->rule->validate('phone', '628123456789', fn() => $passed = false);
        $this->assertTrue($passed);
    }

    public function test_accepts_long_format(): void
    {
        $passed = true;
        $this->rule->validate('phone', '081234567890', fn() => $passed = false);
        $this->assertTrue($passed);
    }

    public function test_rejects_short_number(): void
    {
        $passed = true;
        $this->rule->validate('phone', '0812345', fn() => $passed = false);
        $this->assertFalse($passed);
    }

    public function test_rejects_number_starting_with_07(): void
    {
        $passed = true;
        $this->rule->validate('phone', '07123456789', fn() => $passed = false);
        $this->assertFalse($passed);
    }

    public function test_rejects_invalid_country_code(): void
    {
        $passed = true;
        $this->rule->validate('phone', '+63812345678', fn() => $passed = false);
        $this->assertFalse($passed);
    }

    public function test_rejects_empty_string(): void
    {
        $passed = true;
        $this->rule->validate('phone', '', fn() => $passed = false);
        $this->assertFalse($passed);
    }

    public function test_rejects_non_string_value(): void
    {
        $passed = true;
        $this->rule->validate('phone', 123456789, fn() => $passed = false);
        $this->assertFalse($passed);
    }

    public function test_handles_spaces_and_dashes(): void
    {
        $passed = true;
        $this->rule->validate('phone', '0812 - 3456 - 789', fn() => $passed = false);
        $this->assertTrue($passed);
    }
}
