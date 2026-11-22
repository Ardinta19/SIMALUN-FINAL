<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // Login langsung diarahkan ke dashboard sesuai role (factory user
        // default role 'customer'), bukan lewat hop generic /dashboard.
        $response->assertRedirect(route('customer.dashboard', absolute: false));
    }

    public function test_inactive_users_can_not_authenticate(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Kredensial benar, tapi akun nonaktif harus ditolak & tidak
        // membentuk sesi login.
        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
    public function test_an_existing_session_is_revoked_when_account_is_disabled(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->actingAs($user)->get(route('customer.dashboard'))->assertOk();
        $user->forceFill(['is_active' => false])->save();
        // Profile is auth-only rather than role-scoped, so this also verifies
        // that the check isn't limited to role middleware.
        $this->get(route('profile.edit'))->assertRedirect(route('login'))->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_inactive_session_is_revoked_before_public_or_json_requests(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user)->get(route('welcome'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($user)->getJson(route('profile.edit'))->assertUnauthorized();
        $this->assertGuest();
    }
    public function test_database_default_active_account_is_not_mistaken_for_inactive(): void
    {
        // create() returns an instance without database-default attributes
        // until it is reloaded. actingAs uses that instance directly.
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
