<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Setiap perubahan status pesanan menulis pemberitahuan untuk pelanggan dan
 * admin. Hanya yang sudah dibaca dan cukup tua yang boleh dibersihkan.
 */
class PruneNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function notifikasi(User $user, ?string $readAt, string $createdAt): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\\Notifications\\RentalUpdate',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['rental_order_id' => 1, 'title' => 'Uji', 'message' => 'Uji']),
            'read_at' => $readAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $id;
    }

    public function test_hanya_pemberitahuan_lama_yang_sudah_dibaca_yang_dihapus(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'phone' => '81234567890']);

        $lamaTerbaca = $this->notifikasi($user, now()->subDays(120)->toDateTimeString(), now()->subDays(120)->toDateTimeString());
        $baruTerbaca = $this->notifikasi($user, now()->subDays(3)->toDateTimeString(), now()->subDays(3)->toDateTimeString());
        $lamaBelum = $this->notifikasi($user, null, now()->subDays(200)->toDateTimeString());

        $this->artisan('rental:prune-notifications')->assertExitCode(0);

        $sisa = DB::table('notifications')->pluck('id')->all();

        $this->assertNotContains($lamaTerbaca, $sisa);
        $this->assertContains($baruTerbaca, $sisa);
        $this->assertContains($lamaBelum, $sisa, 'Pemberitahuan yang belum dibaca tidak boleh dihapus.');
    }

    public function test_batas_hari_minimal_30(): void
    {
        $this->artisan('rental:prune-notifications', ['--days' => 5])
            ->expectsOutputToContain('minimal 30')
            ->assertExitCode(1);
    }
}
