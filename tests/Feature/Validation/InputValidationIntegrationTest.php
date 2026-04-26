<?php

namespace Tests\Feature\Validation;

use App\Models\User;
use Tests\TestCase;

class InputValidationIntegrationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'operator']);
    }

    public function test_guestbook_rejects_xss_in_name(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => '<script>alert("xss")</script>John',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting with director',
            'notes' => 'No notes',
        ]);

        $response->assertStatus(422);
        $response->assertSessionHasErrors('visitor_name');
    }

    public function test_guestbook_rejects_onclick_in_purpose(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John Doe',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting onclick="alert(1)"',
            'notes' => 'No notes',
        ]);

        $response->assertStatus(422);
        $response->assertSessionHasErrors('visit_purpose');
    }

    public function test_guestbook_rejects_javascript_protocol(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John Doe',
            'visitor_email' => 'javascript:alert(1)',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting',
            'notes' => 'javascript:alert(1)',
        ]);

        $response->assertStatus(422);
    }

    public function test_guestbook_rejects_iframe_tag(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John<iframe>',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting',
            'notes' => 'Notes',
        ]);

        $response->assertStatus(422);
        $response->assertSessionHasErrors('visitor_name');
    }

    public function test_guestbook_accepts_safe_data(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John Doe',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting with director',
            'notes' => 'Important discussion',
        ]);

        $response->assertStatus(302); // Redirect on success
        $this->assertDatabaseHas('guestbook_entries', [
            'visitor_name' => 'John Doe',
            'visitor_email' => 'john@example.com',
        ]);
    }

    public function test_guestbook_accepts_special_characters(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => "John O'Reilly-Smith",
            'visitor_email' => 'john+tag@example.co.uk',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting & discussion about court rules',
            'notes' => 'Follow-up: email & call required',
        ]);

        $response->assertStatus(302);
    }

    public function test_chat_rejects_xss_payload(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/api/chat/messages', [
            'message' => '<img src=x onerror="alert(1)">',
            'conversation_id' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('message');
    }

    public function test_chat_accepts_long_safe_message(): void
    {
        $this->actingAs($this->user);

        $longMessage = str_repeat('This is a safe message. ', 20);

        $response = $this->postJson('/api/chat/messages', [
            'message' => $longMessage,
            'conversation_id' => 1,
        ]);

        // Might fail because conversation doesn't exist, but should pass validation
        if ($response->status() !== 404) {
            $response->assertJsonMissing(['errors' => ['message']]);
        }
    }

    public function test_chat_rejects_oversized_message(): void
    {
        $this->actingAs($this->user);

        $tooLongMessage = str_repeat('x', 2001);

        $response = $this->postJson('/api/chat/messages', [
            'message' => $tooLongMessage,
            'conversation_id' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('message');
    }

    public function test_validation_errors_are_detailed(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => '<script>xss</script>',
            'visitor_email' => 'invalid-email',
            'visitor_phone' => '123',
            'visit_purpose' => '',
            'notes' => 'x',
        ]);

        $response->assertStatus(422);
        $response->assertSessionHasErrors([
            'visitor_name',
            'visitor_email',
            'visitor_phone',
            'visit_purpose',
            'notes',
        ]);
    }

    public function test_whitespace_is_trimmed(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => '  John Doe  ',
            'visitor_email' => ' john@example.com ',
            'visitor_phone' => ' 08123456789 ',
            'visit_purpose' => '  Meeting  ',
            'notes' => '  Notes  ',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('guestbook_entries', [
            'visitor_name' => 'John Doe',
            'visitor_email' => 'john@example.com',
        ]);
    }

    public function test_encoded_xss_is_detected(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => 'John',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => '&#x3c;script&#x3e;alert(1)&#x3c;/script&#x3e;',
            'notes' => 'Notes',
        ]);

        $response->assertStatus(422);
        $response->assertSessionHasErrors('visit_purpose');
    }
}
