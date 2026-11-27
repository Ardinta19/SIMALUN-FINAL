<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Dasbor admin menghitung antrean dengan `count()` setiap kali dibuka.
 * Indeks pada kolom saringan membuat biayanya tetap datar saat riwayat
 * pesanan bertambah.
 */
class RentalQueueIndexTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, string> */
    private function namaIndeks(string $tabel): array
    {
        return array_map(
            fn (array $indeks) => (string) $indeks['name'],
            Schema::getIndexes($tabel)
        );
    }

    public function test_kolom_antrean_admin_terindeks(): void
    {
        $unit = $this->namaIndeks('rental_order_units');
        $this->assertContains('rental_units_status_progress', $unit);
        $this->assertContains('rental_units_event_on', $unit);
        $this->assertContains('rental_units_ends_on', $unit);
        $this->assertContains('rental_units_starts_on', $unit);

        $this->assertContains('rental_payments_status', $this->namaIndeks('rental_payments'));
    }

    public function test_indeks_lama_tetap_ada(): void
    {
        $this->assertContains('rental_units_availability', $this->namaIndeks('rental_order_units'));
    }
}
