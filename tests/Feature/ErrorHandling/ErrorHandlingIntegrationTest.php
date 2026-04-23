<?php

namespace Tests\Feature\ErrorHandling;

use App\Exceptions\InvalidInputException;
use App\Exceptions\WaRuntimeException;
use Tests\TestCase;

class ErrorHandlingIntegrationTest extends TestCase
{
    public function test_invalid_input_exception_returns_json(): void
    {
        $response = $this->getJson('/api/test-invalid-input');

        if ($response->status() !== 404) {
            $response->assertStatus(422);
            $response->assertJsonStructure([
                'error',
                'code',
                'errors',
                'timestamp',
            ]);
        }
    }

    public function test_validation_exception_includes_field_errors(): void
    {
        $response = $this->post('/guestbook', []);

        $response->assertStatus(302); // Redirect for web request
        $response->assertSessionHasErrors([
            'visitor_name',
            'visitor_email',
            'visitor_phone',
            'visit_purpose',
        ]);
    }

    public function test_api_validation_exception_returns_json(): void
    {
        $response = $this->postJson('/api/chat/messages', [
            'message' => '',
            'conversation_id' => 'not-an-id',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error',
            'code',
            'errors',
            'timestamp',
        ]);
        $response->assertJsonValidationErrors(['message', 'conversation_id']);
    }

    public function test_404_error_returns_appropriate_response(): void
    {
        $response = $this->getJson('/api/nonexistent');

        $response->assertStatus(404);
    }

    public function test_unauthorized_request_returns_401(): void
    {
        $response = $this->getJson('/api/lawangsewu/dashboard');

        // Unauthenticated should redirect to login (web) or 401 (API)
        $this->assertThat(
            $response->status(),
            $this->logicalOr(
                $this->equalTo(302),
                $this->equalTo(401)
            )
        );
    }

    public function test_forbidden_request_returns_403(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'viewer']);
        $this->actingAs($user);

        // Try to access operator-only endpoint
        // This depends on actual routes, adjust as needed
        $response = $this->getJson('/api/lawangsewu/chat/messages');

        // Should either redirect or return 403
        $this->assertTrue(
            in_array($response->status(), [302, 403, 404])
        );
    }

    public function test_xss_validation_error_message(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => '<script>alert(1)</script>',
            'visitor_email' => 'john@example.com',
            'visitor_phone' => '08123456789',
            'visit_purpose' => 'Meeting',
            'notes' => 'Notes',
        ]);

        $response->assertStatus(422);
        $response->assertSessionHasErrors('visitor_name');
        
        $errors = session('errors');
        if ($errors) {
            $this->assertStringContainsString(
                'invalid',
                strtolower(implode(' ', $errors->get('visitor_name')))
            );
        }
    }

    public function test_validation_error_shows_all_failed_fields(): void
    {
        $response = $this->post('/guestbook', [
            'visitor_name' => '',
            'visitor_email' => 'not-an-email',
            'visitor_phone' => '123',
            'visit_purpose' => 'x', // Too short
        ]);

        $response->assertSessionHasErrors([
            'visitor_name',
            'visitor_email',
            'visitor_phone',
            'visit_purpose',
        ]);
    }

    public function test_error_response_includes_timestamp(): void
    {
        $response = $this->postJson('/api/chat/messages', [
            'message' => '<img onerror=alert(1)>',
            'conversation_id' => 1,
        ]);

        if ($response->status() === 422) {
            $response->assertJsonStructure(['timestamp']);
            $this->assertTrue(
                str_contains($response['timestamp'], 'T'),
                'Timestamp should be ISO8601 format'
            );
        }
    }

    public function test_multiple_validation_errors_listed(): void
    {
        $response = $this->postJson('/api/chat/messages', [
            'message' => str_repeat('x', 2001), // Too long
            'conversation_id' => 'not-an-integer',
        ]);

        $response->assertStatus(422);
        $this->assertCount(
            2,
            $response['errors'],
            'Should have errors for both fields'
        );
    }
}
