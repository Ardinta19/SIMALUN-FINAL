<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\RentalUniversity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalUniversityTest extends TestCase
{
    use RefreshDatabase;

    public function test_university_rates_are_snapshotted_and_require_customer_approval(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100000, 'price_unit' => 'day', 'stock' => 1]);
        $this->actingAs($customer)->post(route('admin.rental.universities.store'), ['name' => 'Kampus', 'delivery_fee' => 20000, 'collection_fee' => 30000])->assertRedirect(route('customer.dashboard'));
        $this->assertDatabaseCount('rental_universities', 0);
        $this->actingAs($admin)->post(route('admin.rental.universities.store'), ['name' => 'Kampus', 'delivery_fee' => 20000, 'collection_fee' => 30000])->assertSessionHasNoErrors();
        $university = RentalUniversity::firstOrFail();
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 1]);
        $key = array_key_first(session('rental_cart'));
        $unit = ['starts_on' => now()->addDays(3)->toDateString(), 'event_on' => now()->addDays(3)->toDateString(), 'ends_on' => now()->addDays(3)->toDateString(), 'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'delivery', 'delivery_address' => 'Gedung A', 'delivery_university_id' => $university->id, 'return_method' => 'collection', 'return_address' => 'Gedung A', 'return_university_id' => $university->id];
        $this->post(route('rental.checkout.store'), ['units' => [$key => $unit], 'terms' => '1'])->assertSessionHasNoErrors();
        $ordered = RentalOrder::firstOrFail()->units()->firstOrFail();
        $this->assertSame(50000, (int) $ordered->transport_quote);
        $this->assertNull($ordered->payableAmount());
        $this->actingAs($admin)->put(route('admin.rental.universities.update', $university), ['name' => 'Nama baru', 'delivery_fee' => 1, 'collection_fee' => 2])->assertSessionHasNoErrors();
        $this->post(route('admin.rental.transport.quote', $ordered), ['amount' => 1])->assertSessionHasErrors('transport');
        $this->assertSame('Kampus', $ordered->fresh()->delivery_university);
        $this->actingAs($customer)->post(route('rental.transport.approve', $ordered), ['version' => 1])->assertSessionHasNoErrors();
        $this->assertSame(150000, $ordered->fresh()->payableAmount());
    }
    public function test_university_rate_changes_are_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.rental.universities.store'), ['name' => 'Kampus', 'delivery_fee' => 20000, 'collection_fee' => 30000])->assertSessionHasNoErrors();
        $university = RentalUniversity::firstOrFail();
        $this->put(route('admin.rental.universities.update', $university), ['name' => 'Kampus Baru', 'delivery_fee' => 25000, 'collection_fee' => 35000])->assertSessionHasNoErrors();
        $this->patch(route('admin.rental.universities.toggle', $university))->assertSessionHasNoErrors();
        foreach (['create', 'update', 'toggle'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'rental.university.'.$action, 'auditable_id' => $university->id]);
        }
        $log = \App\Models\AuditLog::where('action', 'rental.university.update')->firstOrFail();
        $this->assertSame(20000, (int) $log->before['delivery_fee']);
        $this->assertSame(25000, (int) $log->after['delivery_fee']);
    }
}
