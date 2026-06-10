<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies customer address coordinates (for the accurate tracking "home"
 * point) are persisted on create/update.
 */
class CustomerAddressCoordinatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_alamat_menyimpan_koordinat_saat_dibuat(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->post(route('customer.addresses.store'), [
            'label' => 'Rumah',
            'recipient_name' => 'Budi',
            'phone' => '081234567890',
            'full_address' => 'Jl. Mawar No. 10, Jambi',
            'latitude' => -1.610100,
            'longitude' => 103.613100,
        ])->assertRedirect(route('customer.addresses.index'));

        $address = CustomerAddress::where('customer_id', $customer->id)->firstOrFail();
        $this->assertEqualsWithDelta(-1.6101, (float) $address->latitude, 0.0001);
        $this->assertEqualsWithDelta(103.6131, (float) $address->longitude, 0.0001);
    }

    public function test_alamat_memperbarui_koordinat_saat_diubah(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Rumah',
            'recipient_name' => 'Budi',
            'full_address' => 'Jl. Mawar No. 10, Jambi',
            'is_primary' => true,
        ]);

        $this->actingAs($customer)->put(route('customer.addresses.update', $address), [
            'label' => 'Rumah',
            'recipient_name' => 'Budi',
            'full_address' => 'Jl. Melati No. 5, Jambi',
            'latitude' => -1.600000,
            'longitude' => 103.600000,
        ])->assertRedirect(route('customer.addresses.index'));

        $address->refresh();
        $this->assertEqualsWithDelta(-1.6, (float) $address->latitude, 0.0001);
        $this->assertEqualsWithDelta(103.6, (float) $address->longitude, 0.0001);
    }
}
