<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_is_per_unit_sequential_and_requires_design_and_payment(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $attrs = ['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed'];
        $unit = $order->units()->create($attrs); $other = $order->units()->create($attrs);
        $url = route('admin.rental.progress.advance', $unit);
        $this->actingAs($customer)->post($url, ['expected_progress' => 'waiting'])->assertRedirect(route('customer.dashboard'));
        $this->actingAs($admin)->post($url, ['expected_progress' => 'waiting'])->assertSessionHasErrors('progress');
        $unit->previews()->create(['version' => 1, 'image_path' => 'test.jpg', 'status' => 'approved', 'created_by' => $admin->id]);
        $this->post($url, ['expected_progress' => 'waiting', 'note' => 'Mulai'])->assertSessionHasNoErrors();
        $this->post($url, ['expected_progress' => 'waiting'])->assertSessionHasErrors('progress');
        $this->post($url, ['expected_progress' => 'production'])->assertSessionHasNoErrors();
        $this->post($url, ['expected_progress' => 'ready'])->assertSessionHasErrors('progress');
        $unit->payments()->create(['amount' => 100, 'proof_path' => 'bukti.jpg', 'status' => 'verified', 'submitted_at' => now(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        $this->post($url, ['expected_progress' => 'ready'])->assertSessionHasNoErrors();
        $this->post($url, ['expected_progress' => 'handed_over'])->assertSessionHasNoErrors();
        $this->assertSame('returned', $unit->fresh()->progress);
        $this->assertSame('waiting', $other->fresh()->progress);
        $this->assertCount(4, $unit->progressEvents);
        $this->actingAs($customer)->get(route('rental.orders.show', $order))->assertOk()->assertSee('Sudah dikembalikan')->assertSee('Mulai');
    }
}
