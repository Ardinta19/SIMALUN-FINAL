<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalAuthPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_registration_use_brand_without_required_animation_cdn(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Berkah Kreatif')->assertSee('berkah-kreatif-logo.jpeg')->assertDontSee('cdnjs.cloudflare.com')->assertDontSee('Azka Laundry');
        $this->get(route('register'))->assertOk()->assertSee('Berkah Kreatif')->assertSee('berkah-kreatif-logo.jpeg')->assertSee('Notifikasi pesanan tersedia di dalam aplikasi')->assertDontSee('cdnjs.cloudflare.com')->assertDontSee('Azka Laundry');
    }
    public function test_login_is_accessible_and_does_not_require_javascript_to_submit(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('<main id="page">', false)
            ->assertSee('name="identifier"', false)->assertSee('id="pw-toggle" hidden', false)
            ->assertSee('aria-live="polite"', false)->assertDontSee('Masuk ke akun SIMALUN kamu');
        $this->from(route('login'))->post(route('login.post'), ['identifier' => 'invalid@example.test', 'password' => 'invalid'])
            ->assertRedirect(route('login'))->assertSessionHasErrors('identifier');
        $this->get(route('login'))->assertOk()->assertSee('id="server-identifier-error"', false)->assertSee('aria-invalid="true"', false);
    }

    public function test_registration_form_has_progressive_and_accessible_controls(): void
    {
        $this->get(route('register'))->assertOk()
            ->assertSee('.form-step { display:block; }', false)
            ->assertSee('register-enhanced', false)->assertSee('role="radiogroup"', false)
            ->assertSee('aria-label="Tampilkan kata sandi"', false)
            ->assertSee('aria-label="Kembali ke data diri"', false)
            ->assertSee('name="terms"', false)->assertDontSee('Daftar sebagai pelanggan SIMALUN');
    }

    public function test_auth_forms_render_safely_after_non_scalar_retry(): void
    {
        $this->from(route('login'))->post(route('login.post'), ['identifier' => ['a@b.test'], 'password' => 'x'])
            ->assertRedirect(route('login'))->assertSessionHasErrors('identifier');
        $this->get(route('login'))->assertOk()->assertDontSee('Array')->assertSee('name="identifier"', false);

        $this->from(route('register'))->post(route('register.post'), ['name' => ['Budi'], 'phone' => ['81234567890'], 'email' => ['a@b.test'], 'terms' => '1'])
            ->assertRedirect(route('register'))->assertSessionHasErrors(['name', 'phone']);
        $this->get(route('register'))->assertOk()->assertDontSee('Array')->assertSee('name="name"', false);

        $this->from(route('password.request'))->post(route('password.email'), ['email' => ['a@b.test']])
            ->assertRedirect(route('password.request'))->assertSessionHasErrors('email');
        $this->get(route('password.request'))->assertOk()->assertDontSee('Array');
    }

    public function test_password_recovery_uses_brand_and_surfaces_status_without_animation_dependencies(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Berkah Kreatif')->assertSee('Lupa kata sandi?')
            ->assertSee(route('password.email'))->assertDontSee('Azka Laundry')->assertDontSee('cdnjs.cloudflare.com');
        $this->withSession(['status' => 'sent'])->get(route('password.request'))->assertOk()->assertSee('Jika email terdaftar');
        $this->withSession(['password_reset_success' => true])->get(route('password.request'))->assertOk()->assertSee('Kata sandi berhasil diubah');
        $this->flushSession();
        $this->get(route('password.reset', ['token' => 'example-token', 'email' => 'nama@example.test']))->assertOk()
            ->assertSee('Buat kata sandi baru')->assertSee('name="token"', false)->assertSee('nama@example.test')->assertSee(route('password.update.reset'));
    }

}
