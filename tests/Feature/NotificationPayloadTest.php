<?php

namespace Tests\Feature;

use App\Notifications\RentalUpdate;
use Tests\TestCase;

/**
 * Muatan notifikasi sewa.
 *
 * Putaran 37 menambahkan `rental_order_unit_id` supaya satu notifikasi tentang
 * satu papan dapat membuka papan itu langsung. Kunci ini sengaja **tidak**
 * dikirim saat notifikasi hanya menyangkut pesanan (mis. "Pesanan sewa baru"),
 * agar tautan lama yang hanya mengenal `rental_order_id` tetap berperilaku sama.
 *
 * Tes ini tidak menyentuh basis data: ia hanya memeriksa muatan yang ditulis
 * `toArray()`, jadi berjalan juga di sandbox tanpa MySQL.
 */
class NotificationPayloadTest extends TestCase
{
    public function test_muatan_notifikasi_pesanan_tanpa_unit_tidak_menambah_kunci_baru(): void
    {
        $payload = (new RentalUpdate(7, 'Pesanan sewa baru', 'Pesanan baru menunggu konfirmasi.'))->toArray($this);

        $this->assertSame(['rental_order_id', 'title', 'message'], array_keys($payload));
        $this->assertSame(7, $payload['rental_order_id']);
    }

    public function test_muatan_notifikasi_unit_menyertakan_id_unit(): void
    {
        $payload = (new RentalUpdate(7, 'Unit dikonfirmasi', 'Tanggalnya tetap ditahan.', 42))->toArray($this);

        $this->assertSame(42, $payload['rental_order_unit_id']);
        $this->assertSame(7, $payload['rental_order_id']);
    }

    public function test_layanan_notifikasi_memakai_id_unit_saat_unitnya_diketahui(): void
    {
        $layanan = (string) file_get_contents(app_path('Services/RentalNotifications.php'));

        // Pintasan per unit harus meneruskan unitnya, bukan hanya pesanannya.
        $this->assertStringContainsString('public function customerForUnit(RentalOrderUnit $unit', $layanan);
        $this->assertStringContainsString('public function adminsForUnit(RentalOrderUnit $unit', $layanan);
        $this->assertMatchesRegularExpression('/customerForUnit\([^)]*\)\s*:\s*void\s*\{\s*\$this->customer\(\$unit->order, \$title, \$message, \$unit\);/s', $layanan);
        $this->assertMatchesRegularExpression('/adminsForUnit\([^)]*\)\s*:\s*void\s*\{\s*\$this->admins\(\$unit->order, \$title, \$message, \$unit\);/s', $layanan);

        // Pengontrol menyimpan tujuan unit di satu tempat saja.
        $controller = (string) file_get_contents(app_path('Http/Controllers/NotificationController.php'));
        $this->assertStringContainsString("\$notif->data['rental_order_unit_id'] ?? null", $controller);
        $this->assertStringContainsString("redirect()->route('rental.orders.unit', [\$rentalOrder, \$unit])", $controller);
        $this->assertStringContainsString("'#unit-'.", $controller);
    }
}
