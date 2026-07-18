<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        // Customer accounts are always provisioned by an admin (with a
        // company_id) via /admin, so public self-registration is disabled.
        $response = $this->get('/register');

        $response->assertStatus(404);
    }
}
