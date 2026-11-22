<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_pages_use_berkah_branding_with_accessible_actions(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound()
            ->assertSee('Berkah Kreatif')->assertSee('berkah-kreatif-logo.jpeg')
            ->assertSee('name="robots"', false)->assertSee('noindex', false)
            ->assertSee('Kembali ke Beranda')->assertDontSee('Azka Laundry');

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('admin.rental.orders'))->assertForbidden()
            ->assertSee('Akses Ditolak')->assertSee('Berkah Kreatif')->assertSee(route('dashboard'))
            ->assertDontSee('Azka Laundry');
    }
}
