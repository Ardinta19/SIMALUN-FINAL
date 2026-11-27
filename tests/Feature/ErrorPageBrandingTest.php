<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_pages_use_berkah_branding_with_accessible_actions(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound()
            ->assertSee('Berkah Kreatif')->assertSee('logo-104.png')
            ->assertSee('name="robots"', false)->assertSee('noindex', false)
            ->assertSee('Kembali ke Beranda')->assertDontSee('Azka Laundry');

        $customer = User::factory()->create(['role' => 'customer']);

        // Peran yang tidak cocok diarahkan ke dashboard sesuai perannya (bukan 403).
        $this->actingAs($customer)->get(route('admin.rental.orders'))->assertRedirect(route('customer.dashboard'));

        // 403 tetap memakai halaman bermerek saat membuka data milik akun lain.
        $other = User::factory()->create(['role' => 'customer']);
        $address = CustomerAddress::create([
            'customer_id' => $other->id,
            'label' => 'Rumah',
            'recipient_name' => 'Pemilik Lain',
            'phone' => '81234567890',
            'full_address' => 'Jalan contoh nomor sepuluh',
            'zone' => 'A',
            'is_primary' => true,
        ]);
        $this->actingAs($customer)->get(route('customer.addresses.edit', $address))->assertForbidden()
            ->assertSee('Akses Ditolak')->assertSee('Berkah Kreatif')->assertSee(route('dashboard'))
            ->assertDontSee('Azka Laundry');
    }
}
