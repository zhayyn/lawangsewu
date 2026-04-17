<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The entrypoint should redirect unauthenticated users to the portal login.
     */
    public function test_the_application_redirects_to_portal_login_when_guest(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(config('lawangsewu.portal_login_url'));
    }
}
