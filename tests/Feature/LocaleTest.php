<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_malay(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Selamat kembali');
    }

    public function test_switching_locale_persists_in_session_and_translates_output(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/locale', ['locale' => 'en']);

        $response->assertRedirect();
        $this->assertSame('en', session('locale'));

        $dashboard = $this->actingAs($user)->get('/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('Welcome back');
        $dashboard->assertDontSee('Selamat kembali');
    }

    public function test_switching_back_to_malay_restores_malay_output(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/locale', ['locale' => 'en']);
        $this->actingAs($user)->post('/locale', ['locale' => 'ms']);

        $this->assertSame('ms', session('locale'));

        $dashboard = $this->actingAs($user)->get('/dashboard');
        $dashboard->assertSee('Selamat kembali');
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/locale', ['locale' => 'fr']);

        $response->assertSessionHasErrors('locale');
        $this->assertNull(session('locale'));
    }
}
