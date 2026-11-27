<?php

namespace Tests\Feature;

use App\Support\AdminQueues;
use App\Support\RentalLabels;
use Tests\TestCase;

/**
 * Putaran 45 — satu label keadaan untuk satu unit.
 *
 * Keluhan dari tangkapan layar: badge hijau "Dikonfirmasi" berdampingan dengan
 * "Tahap 5/5 · Sudah dikembalikan". Dua sumbu berbeda (status pesanan dan
 * progres pengerjaan) ditampilkan sekaligus, sehingga unit yang sudah selesai
 * tetap terbaca belum beres. `RentalLabels::unitState()` memadukannya menjadi
 * satu label dengan empat warna makna.
 *
 * Fungsi ini murni (tanpa basis data) sehingga seluruh cabangnya bisa diuji di
 * sini, termasuk di sandbox tanpa DB.
 */
class UnitStateTest extends TestCase
{
    public function test_status_batal_dan_menunggu_konfirmasi(): void
    {
        $batal = RentalLabels::unitState('cancelled', 'production');
        $this->assertSame('Dibatalkan', $batal['label']);
        $this->assertSame('batal', $batal['tone']);

        $menunggu = RentalLabels::unitState('pending', 'waiting');
        $this->assertSame('Menunggu konfirmasi', $menunggu['label']);
        $this->assertSame('perlu-tindakan', $menunggu['tone']);
    }

    public function test_lima_tahap_terkonfirmasi_punya_label_dan_warna_yang_berbeda(): void
    {
        $harapan = [
            'waiting' => ['Antre pengerjaan', 'perlu-tindakan'],
            'production' => ['Dikerjakan', 'jalan'],
            'ready' => ['Siap diserahkan', 'jalan'],
            'handed_over' => ['Sudah diserahkan', 'jalan'],
            'returned' => ['Selesai', 'selesai'],
        ];

        foreach ($harapan as $progress => [$label, $tone]) {
            $keadaan = RentalLabels::unitState('confirmed', $progress);
            $this->assertSame($label, $keadaan['label'], "Label untuk {$progress} salah.");
            $this->assertSame($tone, $keadaan['tone'], "Warna untuk {$progress} salah.");
        }

        // Unit yang sudah kembali bukan lagi "perlu tindakan".
        $this->assertArrayNotHasKey('next', RentalLabels::unitState('confirmed', 'returned'));
    }

    public function test_progress_kosong_diperlakukan_sebagai_langkah_pertama(): void
    {
        $this->assertSame('Antre pengerjaan', RentalLabels::unitState('confirmed', null)['label']);
        $this->assertSame('Dikerjakan', RentalLabels::unitState('confirmed', 'production')['label']);
    }

    public function test_satu_unit_tidak_mengulang_daftar_isian_di_detail_pesanan(): void
    {
        $detail = (string) file_get_contents(resource_path('views/rental/admin-detail.blade.php'));

        // Daftar "Item yang dipesan" hanya untuk pesanan multi-unit; untuk satu
        // unit isinya mengulang kartu unit di bawahnya.
        $this->assertStringContainsString('@if($order->units->count() > 1)', $detail);
        $this->assertStringContainsString("rental._ordered_items", $detail);

        // Batas konfirmasi tidak relevan setelah semua unit dikonfirmasi.
        $this->assertStringContainsString("where('status', 'pending')->isNotEmpty()", $detail);

        // Badge status lama diganti label keadaan.
        $this->assertStringContainsString("RentalLabels::unitState(\$unit->status, \$tahapKunci)", $detail);
        $this->assertStringNotContainsString('unitStatusLabels', $detail);

        // Panel tindakan tidak muncul untuk unit yang sudah selesai.
        $this->assertStringContainsString("! (\$unit->status === 'confirmed' && (\$unit->progress ?: 'waiting') === 'returned')", $detail);
    }

    public function test_jadwal_tidak_mengulang_kalimat_acara_hari_ini(): void
    {
        // Komentar Blade boleh menyebut kalimat lama; yang diperiksa markupnya.
        $jadwal = (string) preg_replace('/\{\{--.*?--\}\}/s', ' ',
            (string) file_get_contents(resource_path('views/rental/schedule.blade.php')));

        $this->assertStringNotContainsString('Acara hari ini', $jadwal);
        $this->assertStringContainsString('RentalLabels::unitState($unit->status, $unit->progress)', $jadwal);
        $this->assertStringContainsString('adm-unitline__today', $jadwal);
        $this->assertStringContainsString('<b>Serah terima</b>', $jadwal);
        $this->assertStringContainsString('<b>Pengembalian</b>', $jadwal);

        // Lembar serah terima hanya ditawarkan pada hari yang punya serah terima.
        $this->assertStringContainsString('@if($terima)', $jadwal);
        $this->assertStringContainsString('Lembar serah terima ({{ $terima }} unit)', $jadwal);

        // Nomor pesanan ikut tampil agar unit bisa dicocokkan dengan daftar.
        $this->assertStringContainsString('adm-ref">{{ $unit->order->reference }}', $jadwal);
    }

    public function test_antrean_kerja_urut_alur_dan_punya_angka(): void
    {
        $daftar = (string) file_get_contents(resource_path('views/rental/admin.blade.php'));
        $this->assertStringContainsString('adm-chipbar--queue', $daftar);
        $this->assertStringContainsString('adm-chip__count', $daftar);
        $this->assertStringContainsString('sesuai alur', $daftar);

        // Satu kendali filter mengenali status unit maupun tugas.
        $this->assertStringContainsString('<optgroup label="Status unit">', $daftar);
        $this->assertStringContainsString('<optgroup label="Tugas">', $daftar);

        // Urutan alur kerja dinyatakan di kepala halaman.
        $this->assertStringContainsString('konfirmasi → desain → ongkos → pembayaran → serah terima', $daftar);

        // Definisi antrean ada di satu tempat dan urut sesuai alur. Diperiksa
        // dari berkasnya, bukan dengan memanggil `counts()`: fungsi itu menembak
        // basis data, sedangkan tes ini harus tetap berjalan tanpa DB.
        $sumber = (string) file_get_contents(app_path('Support/AdminQueues.php'));
        preg_match_all("/'key' => '([a-z_]+)'/", $sumber, $cocok);
        $this->assertSame(['pending', 'design', 'transport', 'payment', 'review', 'return_overdue'], $cocok[1]);
    }

    public function test_setiap_nilai_filter_punya_label_manusia(): void
    {
        // Daftar ini harus sama dengan Rule::in() di RentalOrderController::adminIndex.
        $nilai = ['pending', 'confirmed', 'cancelled', 'payment_pending', 'design_pending',
            'review', 'transport_quote', 'transport_approval', 'design_initial',
            'design_revision', 'seller_cancelled', 'grace', 'return_overdue'];

        $label = AdminQueues::labels();

        foreach ($nilai as $satu) {
            $this->assertArrayHasKey($satu, $label, "Filter {$satu} tidak punya label.");
            $this->assertNotSame($satu, $label[$satu], "Label {$satu} masih berupa kunci basis data.");
        }

        // Tiga status unit, sisanya tugas.
        foreach (['pending', 'confirmed', 'cancelled'] as $status) {
            $this->assertTrue(AdminQueues::isUnitStatus($status), $status.' seharusnya status unit.');
        }
        foreach (['payment_pending', 'design_initial', 'transport_quote', 'review', 'return_overdue'] as $tugas) {
            $this->assertFalse(AdminQueues::isUnitStatus($tugas), $tugas.' seharusnya tugas, bukan status unit.');
        }

        // Nama antrean menang atas kata status yang lebih panjang.
        $this->assertSame('Bukti pembayaran', AdminQueues::label('payment_pending'));
        $this->assertSame('Menunggu konfirmasi', AdminQueues::label('pending'));
    }

    public function test_chip_filter_memakai_awalan_status_atau_tugas(): void
    {
        $daftar = (string) file_get_contents(resource_path('views/rental/admin.blade.php'));

        $this->assertStringContainsString("AdminQueues::isUnitStatus(\$filters['status']) ? 'Status: ' : 'Tugas: '", $daftar);
        $this->assertStringContainsString("AdminQueues::label(\$filters['status'])", $daftar);
        $this->assertStringNotContainsString("'Status: '.(\\App\\Support\\RentalLabels::UNIT_STATUS", $daftar);
    }
}
