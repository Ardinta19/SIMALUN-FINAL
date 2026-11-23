<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\RentalPaymentSetting;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_payment_instructions_visible_to_owner_with_wib_deadline(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-22 12:00:00', 'Asia/Jakarta'));
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => '2026-12-02', 'event_on' => '2026-12-02', 'ends_on' => '2026-12-02', 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed']);
        $url = route('admin.rental.payment-settings.update');
        $this->actingAs($customer)->put($url, ['instructions' => 'Jangan tersimpan'])->assertRedirect(route('customer.dashboard'));
        $this->assertDatabaseCount('rental_payment_settings', 0);
        $this->actingAs($admin)->put($url, ['instructions' => 'Transfer ke rekening usaha'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rental_payment_settings', ['id' => 1, 'instructions' => 'Transfer ke rekening usaha']);
        $this->assertSame('2026-12-01 23:59:59', $unit->plannedPaymentDeadline()->format('Y-m-d H:i:s'));
        $this->actingAs($customer)->get(route('rental.orders.unit', [$order, $unit]))->assertOk()->assertSee('Transfer ke rekening usaha')->assertSee('01/12/2026 23:59');
    }
    public function test_admin_configures_qris_and_whatsapp_for_customer_and_can_replace_or_remove_qris(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed']);
        $url = route('admin.rental.payment-settings.update');
        $this->actingAs($customer)->post($url, ['_method' => 'PUT', 'qris' => UploadedFile::fake()->image('qr.png')])->assertRedirect(route('customer.dashboard'));
        $this->assertDatabaseCount('rental_payment_settings', 0);
        $this->actingAs($admin)->post($url, ['_method' => 'PUT', 'qris' => UploadedFile::fake()->image('qr.png'), 'whatsapp_number' => '6281234567890'])->assertSessionHasNoErrors();
        $path = RentalPaymentSetting::findOrFail(1)->qris_path;
        Storage::disk('local')->assertExists($path);
        // Tamu tidak boleh melihat gambar QRIS pembayaran. actingAs($admin) di
        // atas masih menempel pada test ini, jadi guard auth dikosongkan dulu.
        $this->app['auth']->forgetGuards();
        $this->get(route('rental.payment.qris'))->assertRedirect(route('login'));
        $response = $this->actingAs($customer)->get(route('rental.payment.qris'))->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('rental.orders.unit', [$order, $unit]))->assertOk()->assertSee('Bayar melalui QRIS')->assertSee('https://wa.me/6281234567890', false)->assertSee('Kirim bukti pembayaran');
        $this->actingAs($admin)->post($url, ['_method' => 'PUT', 'whatsapp_number' => 'https://example.com'])->assertSessionHasErrors('whatsapp_number');
        $this->post($url, ['_method' => 'PUT', 'remove_qris' => 1, 'qris' => UploadedFile::fake()->image('other.png')])->assertSessionHasErrors('qris');
        $this->post($url, ['_method' => 'PUT', 'qris' => UploadedFile::fake()->image('other.png')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($path);
        $newPath = RentalPaymentSetting::findOrFail(1)->qris_path;
        Storage::disk('local')->assertExists($newPath);
        $this->post($url, ['_method' => 'PUT', 'remove_qris' => 1])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($newPath);
        $this->get(route('rental.payment.qris'))->assertNotFound();
    }
}
