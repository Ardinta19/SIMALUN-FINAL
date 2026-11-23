<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tenggat respons desain dan keputusan admin "lanjutkan dengan catatan".
 * Tenggat = 48 jam setelah preview diunggah, dimajukan ke 3 hari sebelum acara,
 * tetapi tidak pernah sebelum preview diunggah.
 */
class RentalDesignDeadlineTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:\App\Models\RentalOrderUnit,1:User,2:User} */
    private function context(string $startsOn = '2026-12-10'): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $category = RentalCategory::firstOrCreate(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Papan', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 5]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create([
            'rental_product_id' => $product->id, 'product_name' => 'Papan', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100,
            'starts_on' => $startsOn, 'event_on' => $startsOn, 'ends_on' => $startsOn,
            'recipient_name' => 'Penerima', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff',
            'status' => 'confirmed', 'progress' => 'waiting',
        ]);

        return [$unit, $admin, $customer];
    }

    private function override(User $admin, $unit, array $extra = [])
    {
        return $this->actingAs($admin)->post(route('admin.rental.design.override', $unit), array_merge([
            'note' => 'Pelanggan tidak merespons; acara harus tetap dikerjakan.',
            'confirmed' => 1,
        ], $extra));
    }

    public function test_override_needs_the_deadline_and_a_written_reason(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00:00', 'Asia/Jakarta'));
        try {
            [$unit, $admin, $customer] = $this->context();
            $this->actingAs($admin)->post(route('admin.rental.design.store', $unit), ['image' => UploadedFile::fake()->image('preview.jpg')])->assertSessionHasNoErrors();
            $preview = $unit->previews()->firstOrFail();

            // Tanpa konfirmasi tertulis.
            $this->override($admin, $unit, ['confirmed' => null])->assertSessionHasErrors('confirmed');

            // Sebelum 48 jam sejak preview diunggah.
            Carbon::setTestNow(Carbon::parse('2026-12-03 08:59:00', 'Asia/Jakarta'));
            $this->override($admin, $unit)->assertSessionHasErrors('design');
            $this->post(route('admin.rental.progress.advance', $unit), ['expected_progress' => 'waiting'])->assertSessionHasErrors('progress');

            // Tepat setelah tenggat: keputusan diterima dan pengerjaan boleh dimulai.
            Carbon::setTestNow(Carbon::parse('2026-12-03 09:01:00', 'Asia/Jakarta'));
            $this->override($admin, $unit)->assertSessionHasNoErrors();
            $fresh = $unit->fresh();
            $this->assertNotNull($fresh->design_override_at);
            $this->assertSame($admin->id, $fresh->design_override_by);
            $this->assertSame($preview->version, $fresh->design_override_version);
            $this->assertTrue($fresh->designOverrideActive());
            $this->assertTrue($fresh->designApprovedForProduction());
            $this->assertStringContainsString('tanpa persetujuan', $unit->progressEvents()->get()->last()->note);

            $this->post(route('admin.rental.progress.advance', $unit), ['expected_progress' => 'waiting'])->assertSessionHasNoErrors();
            $this->assertSame('production', $unit->fresh()->progress);
            $this->assertSame('pending', $preview->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_new_preview_version_needs_its_own_approval(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00:00', 'Asia/Jakarta'));
        try {
            [$unit, $admin, $customer] = $this->context();
            $this->actingAs($admin)->post(route('admin.rental.design.store', $unit), ['image' => UploadedFile::fake()->image('v1.jpg')])->assertSessionHasNoErrors();

            Carbon::setTestNow(Carbon::parse('2026-12-03 09:01:00', 'Asia/Jakarta'));
            $this->override($admin, $unit)->assertSessionHasNoErrors();
            $this->assertTrue($unit->fresh()->designOverrideActive());

            // Versi baru selalu memerlukan persetujuan sendiri.
            $this->actingAs($admin)->post(route('admin.rental.design.store', $unit), ['image' => UploadedFile::fake()->image('v2.jpg')])->assertSessionHasNoErrors();
            $this->assertNull($unit->fresh()->design_override_at);
            $this->assertFalse($unit->fresh()->designApprovedForProduction());
            $this->post(route('admin.rental.progress.advance', $unit), ['expected_progress' => 'waiting'])->assertSessionHasErrors('progress');

            $v2 = $unit->previews()->firstOrFail();
            $this->assertSame(2, $v2->version);
            $this->actingAs($customer)->post(route('rental.design.respond', $v2), ['decision' => 'approved'])->assertSessionHasNoErrors();
            // Kembali sebagai admin: rute progres hanya untuk admin.
            $this->actingAs($admin)->post(route('admin.rental.progress.advance', $unit), ['expected_progress' => 'waiting'])->assertSessionHasNoErrors();
            $this->assertSame('production', $unit->fresh()->progress);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_near_event_order_can_be_decided_right_after_the_preview_is_uploaded(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00:00', 'Asia/Jakarta'));
        try {
            // Acara 2 hari lagi: tenggat tidak boleh menyalip waktu unggah.
            [$unit, $admin] = $this->context('2026-12-03');
            $this->actingAs($admin)->post(route('admin.rental.design.store', $unit), ['image' => UploadedFile::fake()->image('preview.jpg')])->assertSessionHasNoErrors();

            Carbon::setTestNow(Carbon::parse('2026-12-01 09:05:00', 'Asia/Jakarta'));
            $this->override($admin, $unit)->assertSessionHasNoErrors();
            $this->assertTrue($unit->fresh()->designApprovedForProduction());
            $this->post(route('admin.rental.progress.advance', $unit), ['expected_progress' => 'waiting'])->assertSessionHasNoErrors();
            $this->assertSame('production', $unit->fresh()->progress);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_customer_response_clears_the_override(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00:00', 'Asia/Jakarta'));
        try {
            [$unit, $admin, $customer] = $this->context();
            $this->actingAs($admin)->post(route('admin.rental.design.store', $unit), ['image' => UploadedFile::fake()->image('preview.jpg')])->assertSessionHasNoErrors();
            $preview = $unit->previews()->firstOrFail();

            Carbon::setTestNow(Carbon::parse('2026-12-03 09:01:00', 'Asia/Jakarta'));
            $this->override($admin, $unit)->assertSessionHasNoErrors();

            // Pelanggan akhirnya menyetujui: keputusan admin tidak diperlukan lagi.
            $this->actingAs($customer)->post(route('rental.design.respond', $preview), ['decision' => 'approved'])->assertSessionHasNoErrors();
            $this->assertNull($unit->fresh()->design_override_at);
            $this->assertTrue($unit->fresh()->designApprovedForProduction());
        } finally {
            Carbon::setTestNow();
        }
    }
}
