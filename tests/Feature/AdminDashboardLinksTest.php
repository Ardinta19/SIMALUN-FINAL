<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Dasbor admin: setiap angka "Hari ini" harus menuju halaman yang benar-benar
 * memuat angka itu.
 *
 * Temuan UAT: sel "papan dikembalikan" menaut ke lembar harian, padahal lembar
 * itu hanya memuat serah terima — admin menekan angka 2 dan mendarat di halaman
 * yang tidak memuat satu pun pengembalian. Sel ketiga juga mengulang "unit
 * aktif" yang sudah ada di grid statistik tepat di bawahnya.
 */
class AdminDashboardLinksTest extends TestCase
{
    public function test_sel_pengembalian_menuju_jadwal_dengan_kegiatan_pengembalian(): void
    {
        $view = (string) file_get_contents(resource_path('views/roles/admin/dashboard.blade.php'));

        $this->assertStringContainsString(
            "route('admin.rental.schedule', ['month' => \$hariIni->format('Y-m'), 'activity' => 'returning'])",
            $view
        );
        // Angka pengembalian tidak lagi menaut ke lembar harian (isinya serah terima).
        $this->assertDoesNotMatchRegularExpression(
            '/\$hariIniKembali.*daily-sheet/s',
            $view,
            'Sel pengembalian masih menaut ke lembar harian.'
        );
    }

    public function test_sel_ketiga_adalah_acara_hari_ini_bukan_ulangan_unit_aktif(): void
    {
        $view = (string) file_get_contents(resource_path('views/roles/admin/dashboard.blade.php'));
        $controller = (string) file_get_contents(app_path('Http/Controllers/DashboardController.php'));

        $this->assertStringContainsString('papan dipakai acara', $view);
        $this->assertStringContainsString("\$hariIniAcara", $view);
        $this->assertStringContainsString('hariIniAcara', $controller);
        $this->assertStringContainsString("where('event_on', \$hariIni->toDateString())", $controller);

        // "unit aktif" sudah punya tempatnya di grid statistik; tidak diulang
        // sebagai sel ketiga pada kartu "Hari ini".
        $this->assertStringNotContainsString('adm-today__label">unit aktif', $view);
    }

    public function test_tautan_hari_ini_memakai_anchor_kartu_yang_benar(): void
    {
        $view = (string) file_get_contents(resource_path('views/roles/admin/dashboard.blade.php'));
        $jadwal = (string) file_get_contents(resource_path('views/rental/schedule.blade.php'));

        // Dasbor memakai #hari-ini; kartu hari ini di jadwal memang ber-id itu.
        $this->assertStringContainsString("? '#hari-ini' : ''", $view);
        $this->assertStringContainsString("'hari-ini' : 'hari-'.\$tanggal", $jadwal);

        // Kegiatan yang dipakai tautan dasbor memang diterima halaman jadwal.
        $this->assertStringContainsString("'receiving', 'event', 'returning'", (string) file_get_contents(app_path('Http/Controllers/RentalScheduleController.php')));
    }
}
