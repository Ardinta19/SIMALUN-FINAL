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
