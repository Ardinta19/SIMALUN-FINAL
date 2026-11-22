<?php

namespace Tests\Feature;

use App\Models\FinanceEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalCashBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_and_edit_manual_entries_but_not_payment_entries(): void
    {
        $admin = User::factory()->create(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = User::factory()->create(); $customer->forceFill(['role' => 'customer'])->save();
        $data = ['entry_date' => '2026-11-15', 'entry_type' => 'income', 'category' => 'Modal', 'amount' => 10000, 'notes' => 'Modal usaha'];
        $this->actingAs($customer)->post(route('admin.rental.cashbook.store'), $data)->assertRedirect(route('customer.dashboard'));
        $this->actingAs($admin)->post(route('admin.rental.cashbook.store'), $data)->assertSessionHasErrors('not_rental_payment');
        $data['not_rental_payment'] = 1;
        $this->post(route('admin.rental.cashbook.store'), $data)->assertSessionHasNoErrors();
        $entry = FinanceEntry::where('source_type', 'manual')->firstOrFail();
        $this->put(route('admin.rental.cashbook.update', $entry), array_merge($data, ['category' => 'Investasi', 'amount' => 12000]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('finance_entries', ['id' => $entry->id, 'category' => 'Investasi', 'amount' => 12000]);
        $payment = FinanceEntry::create(['entry_date' => '2026-11-15', 'period_key' => '2026-11', 'entry_type' => 'income', 'category' => 'income', 'amount' => 4000, 'source_type' => 'rental_payment', 'source_id' => 1]);
        $this->put(route('admin.rental.cashbook.update', $payment), $data)->assertForbidden();
        $this->get(route('admin.rental.cashbook.index', ['period' => 'month', 'date' => '2026-11-15']))->assertOk()->assertSee('12.000')->assertSee('4.000')->assertSee('16.000');
    }
}
