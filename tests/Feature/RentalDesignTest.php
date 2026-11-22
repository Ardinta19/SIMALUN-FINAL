<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_request_revision_and_other_customer_cannot_view_preview(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $other = User::factory()->create(); $other->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 1]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff', 'status' => 'confirmed']);
        $this->actingAs($admin)->post(route('admin.rental.design.store', $unit), ['image' => UploadedFile::fake()->image('preview.jpg')])->assertSessionHasNoErrors();
        $preview = $unit->previews()->firstOrFail();
        $this->actingAs($other)->get(route('rental.design.image', $preview))->assertForbidden();
        $this->actingAs($other)->post(route('rental.design.respond', $preview), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($customer)->get(route('rental.design.image', $preview))->assertOk();
        $this->post(route('rental.design.respond', $preview), ['decision' => 'revision', 'note' => 'Ubah warna'])->assertSessionHasNoErrors();
        $this->assertSame('revision', $preview->fresh()->status);
        $this->post(route('rental.design.respond', $preview), ['decision' => 'approved'])->assertSessionHasErrors('design');
    }
}
