<?php

namespace Tests\Feature;

use App\Models\RentalPaymentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_help_uses_rental_guidance_and_only_configured_contact(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($customer)->get(route('customer.help'))->assertOk()->assertSee('Pusat bantuan')->assertSee('Lompat ke bagian bantuan')->assertSee('aria-label="Navigasi utama"', false)->assertSee('Nomor WhatsApp belum diatur')->assertSee('QRIS')->assertDontSee('Azka Laundry')->assertDontSee('6281234567890');
        RentalPaymentSetting::create(['id' => 1, 'whatsapp_number' => '6289876543210']);
        $this->get(route('customer.help'))->assertOk()->assertSee('https://wa.me/6289876543210', false)->assertSee('Buka WhatsApp');
        $this->get(route('admin.help'))->assertRedirect(route('customer.dashboard'));
        $this->actingAs($admin)->get(route('admin.help'))->assertOk()->assertSee('Panduan admin')->assertSee('buku kas')->assertDontSee('Azka Laundry')->assertDontSee('Tugaskan Kurir');
    }
}
