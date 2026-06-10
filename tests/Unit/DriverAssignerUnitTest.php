<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\DriverAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit test pelengkap untuk DriverAssigner — fokus pada cabang yang
 * belum dicakup feature test: fallback strategi tak dikenal, dan rotasi
 * berurutan setelah markAssigned().
 */
class DriverAssignerUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_strategi_tidak_dikenal_fallback_ke_round_robin(): void
    {
        // Strategi ngawur harus jatuh ke default round_robin (match arm
        // default), bukan error.
        config(['laundry.driver_assignment_strategy' => 'strategi-ngawur']);

        $driverLama = User::factory()->create(['role' => 'driver']);
        $driverLama->forceFill(['last_assigned_at' => now()->subDays(2)])->save();

        $driverBaru = User::factory()->create(['role' => 'driver']);
        $driverBaru->forceFill(['last_assigned_at' => now()->subMinutes(1)])->save();

        $picked = DriverAssigner::pick();

        // Perilaku round_robin: pilih yang paling lama tidak di-assign.
        $this->assertSame($driverLama->id, $picked->id);
    }

    public function test_mark_assigned_membuat_pick_berikutnya_berpindah_driver(): void
    {
        config(['laundry.driver_assignment_strategy' => 'round_robin']);

        $driverA = User::factory()->create(['role' => 'driver', 'name' => 'A']);
        $driverB = User::factory()->create(['role' => 'driver', 'name' => 'B']);

        $pertama = DriverAssigner::pick();
        DriverAssigner::markAssigned($pertama);

        $kedua = DriverAssigner::pick();

        // Setelah driver pertama ditandai, rotasi harus pindah ke driver
        // satunya (yang last_assigned_at-nya masih null).
        $this->assertNotSame($pertama->id, $kedua->id);
        $this->assertContains($pertama->id, [$driverA->id, $driverB->id]);
        $this->assertContains($kedua->id, [$driverA->id, $driverB->id]);
    }
}
