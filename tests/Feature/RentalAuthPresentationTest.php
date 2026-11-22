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
}
