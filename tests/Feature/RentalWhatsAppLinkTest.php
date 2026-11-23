<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tautan WhatsApp siap kirim: pelanggan dapat menghubungi admin dan admin dapat
 * menghubungi pelanggan dengan pesan yang sudah memuat konteks unit.
 */
class RentalWhatsAppLinkTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:User,2:RentalOrder,3:\App\Models\RentalOrderUnit} */
    private function context(?string $customerPhone = '0812-3456-7890'): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'phone' => $customerPhone]);
        $category = RentalCategory::firstOrCreate(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 3]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create([
            'rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100,
            'starts_on' => '2026-12-20', 'event_on' => '2026-12-20', 'ends_on' => '2026-12-21',
            'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
            'status' => 'confirmed', 'progress' => 'waiting',
        ]);

        return [$admin, $customer, $order, $unit];
    }

    public function test_customer_can_message_admin_with_unit_context(): void
    {
        config(['laundry.whatsapp' => '0812-9999-8888']);
        [, $customer, $order, $unit] = $this->context();
        $unit->previews()->create(['version' => 1, 'image_path' => 'desain.jpg', 'status' => 'pending', 'created_by' => $customer->id]);

        $response = $this->actingAs($customer)->get(route('rental.orders.unit', [$order, $unit]))->assertOk();
        $response->assertSee('Tanya admin via WhatsApp');
        // Nomor dinormalkan dari 0812... menjadi 62812..., pesan sudah terisi.
        $response->assertSee('https://wa.me/6281299998888?text=', false);
        $response->assertSee(rawurlencode($order->reference), false);
        $response->assertSee(rawurlencode('menunggu persetujuan desain versi 1'), false);
    }

    public function test_admin_can_message_customer_and_link_hides_without_valid_phone(): void
    {
        [$admin, , $order, $unit] = $this->context();
        $unit->payments()->create(['amount' => 10, 'proof_path' => 'bukti.jpg', 'submitted_at' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.rental.orders.show', $order))
            ->assertOk()
            ->assertSee('Chat pelanggan via WhatsApp')
            ->assertSee('https://wa.me/6281234567890?text=', false)
            ->assertSee(rawurlencode('menunggu verifikasi bukti pembayaran'), false);

        // Nomor tidak masuk akal: tombol disembunyikan, bukan tautan rusak.
        $order->customer->forceFill(['phone' => 'abc'])->save();
        $this->actingAs($admin)
            ->get(route('admin.rental.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Chat pelanggan via WhatsApp');
    }

    public function test_customer_link_is_hidden_when_business_number_is_not_set(): void
    {
        config(['laundry.whatsapp' => '']);
        [, $customer, $order, $unit] = $this->context();

        $this->actingAs($customer)->get(route('rental.orders.unit', [$order, $unit]))
            ->assertOk()
            ->assertDontSee('Tanya admin via WhatsApp');
    }
}
