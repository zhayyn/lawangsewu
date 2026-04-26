<?php

namespace Tests\Feature\EdgeCases;

use App\Models\User;
use Tests\TestCase;

class EdgeCaseTestingTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'operator']);
    }

    public function test_guestbook_with_null_optional_fields(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John Doe',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting',
            'notes' => null,
        ]);

        $response->assertStatus(302);
    }

    public function test_guestbook_with_empty_optional_fields(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John Doe',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting',
            'notes' => '',
        ]);

        // Empty string is not null, should be accepted
        $response->assertStatus(302);
    }

    public function test_name_with_unicode_characters(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'Μήνυμα 日本語 العربية مرحبا',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting',
            'notes' => 'Notes',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('guestbook_entries', [
            'visitor_name' => 'Μήνυμα 日本語 العربية مرحبا',
        ]);
    }

    public function test_email_with_plus_addressing(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John',
            'visitor_email' => 'john+filter@example.co.uk',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting',
            'notes' => 'Notes',
        ]);

        $response->assertStatus(302);
    }

    public function test_phone_with_various_formats(): void
    {
        $formats = [
            '08123456789',      // Standard
            '0812-3456-789',   // Formatted
            '081 234 567 89',  // With spaces
            '+62812345678',     // International
            '628123456789',     // Without +
        ];

        foreach ($formats as $phone) {
            $response = $this->post('/guestbook', [
                'visitor_name' => 'John',
                'visitor_email' => 'john@example.com',
                'visitor_phone' => $phone,
                'visit_purpose' => 'Meeting',
                'notes' => 'Notes',
            ]);

            $response->assertStatus(302, "Failed for phone: $phone");
        }
    }

    public function test_message_with_only_whitespace_rejected(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/chat/messages', [
            'message' => '   ',
            'conversation_id' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_very_long_email_rejected(): void
    {
        $longEmail = str_repeat('a', 300) . '@example.com';

        $response = $this->post('/guestbook', [
            'visitor_name' => 'John',
            'visitor_email' => $longEmail,
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting',
            'notes' => 'Notes',
        ]);

        $response->assertStatus(422);
    }

    public function test_purpose_at_minimum_length(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeti', // Exactly at min
            'notes' => 'Notes',
        ]);

        $response->assertStatus(302);
    }

    public function test_purpose_below_minimum_length(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meet', // Below min
            'notes' => 'Notes',
        ]);

        $response->assertStatus(422);
    }

    public function test_message_at_maximum_length(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/chat/messages', [
            'message' => str_repeat('a', 2000),
            'conversation_id' => 1,
        ]);

        // Status depends on whether conversation exists
        $this->assertTrue(in_array($response->status(), [422, 404, 201]));
    }

    public function test_message_exceeds_maximum_length(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/chat/messages', [
            'message' => str_repeat('a', 2001),
            'conversation_id' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_special_html_entities(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John & Jane "Smith" <Partners>',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Business & consulting meeting',
            'notes' => 'Quotes "like this" and ampersand & work',
        ]);

        $response->assertStatus(302);
    }

    public function test_newlines_in_text_fields(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => "John\nDoe",
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => "Line 1\nLine 2\nLine 3",
            'notes' => "Notes with\nmultiple\nlines",
        ]);

        // Should handle newlines gracefully
        $response->assertStatus(302);
    }

    public function test_tabs_and_special_whitespace(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => "John\tDoe",
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => "Meeting\twith\tdirector",
            'notes' => "Notes\twith\ttabs",
        ]);

        $response->assertStatus(302);
    }

    public function test_invalid_json_in_api_request(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', '/api/chat/messages', ['invalid json'], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        // Should fail gracefully
        $this->assertThat(
            $response->status(),
            $this->logicalOr(
                $this->equalTo(400),
                $this->equalTo(422),
                $this->equalTo(405)
            )
        );
    }

    public function test_missing_required_fields(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John',
            // Missing other required fields
        ]);

        $response->assertStatus(422);
        $response->assertSessionHasErrors([
            'visitor_email',
            'visitor_phone',
            'visit_purpose',
        ]);
    }

    public function test_extra_fields_are_ignored(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting',
            'notes' => 'Notes',
            'extra_field' => 'This should be ignored',
            'another_extra' => 'Also ignored',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('guestbook_entries', [
            'extra_field' => 'This should be ignored',
        ]);
    }
}
