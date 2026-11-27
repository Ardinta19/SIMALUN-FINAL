<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `.env.example` adalah satu-satunya petunjuk yang dibaca orang saat memasang
 * aplikasi ini di server. Kalau baris pengingat produksi terhapus, kesalahan
 * barunya tidak kelihatan sampai ada pelanggan yang menunggu surel pemulihan
 * kata sandi yang tidak pernah dikirim (bawaan `MAIL_MAILER=log`).
 */
class EnvExampleTest extends TestCase
{
    private function contoh(): string
    {
        return File::get(base_path('.env.example'));
    }

    private static function kunciUsaha(): array
    {
        return ['BUSINESS_NAME', 'BUSINESS_TAGLINE', 'BUSINESS_ADDRESS', 'BUSINESS_GMAPS', 'BUSINESS_PHONE_DISPLAY', 'BUSINESS_WA'];
    }

    private function produksi(): string
    {
        return File::get(base_path('.env.production.example'));
    }

    public function test_pengingat_setelan_produksi_lengkap(): void
    {
        $isi = $this->contoh();

        $wajib = [
            'APP_ENV=production',
            'APP_DEBUG=false',
            'APP_URL=https://',
            'SESSION_SECURE_COOKIE=true',
            'MAIL_MAILER=smtp',
            'MAIL_FROM_ADDRESS',
            'LOG_LEVEL=warning',
            'TRUSTED_PROXIES',
            'config:cache',
        ];

        foreach ($wajib as $baris) {
            $this->assertStringContainsString($baris, $isi, '.env.example harus memuat pengingat "'.$baris.'".');
        }
    }

    public function test_bawaan_lokal_tetap_untuk_pengembangan(): void
    {
        $isi = $this->contoh();

        $this->assertStringContainsString('MAIL_MAILER=log', $isi);
        $this->assertStringContainsString('APP_DEBUG=true', $isi);
        $this->assertStringContainsString('SESSION_DRIVER=database', $isi);
    }

    public function test_contoh_produksi_tidak_lagi_membawa_identitas_laundry(): void
    {
        $isi = $this->produksi();

        foreach (self::kunciUsaha() as $kunci) {
            $this->assertStringContainsString($kunci.'=', $isi, '.env.production.example harus memuat '.$kunci.'.');
        }

        // Kunci aplikasi laundry lama (termasuk dua kunci driver yang tidak
        // pernah dibaca kode rental) tidak boleh muncul lagi di contoh
        // produksi: siapa pun yang menyalin berkas ini akan mengira nilainya
        // masih berpengaruh.
        foreach (['LAUNDRY_', 'DRIVER_REALTIME_MODE', 'DRIVER_ASSIGNMENT_STRATEGY', 'Azka'] as $sisa) {
            $this->assertStringNotContainsString($sisa, $isi, 'Sisa konfigurasi laundry/produksi lama: '.$sisa.'.');
        }
    }

    public function test_kedua_contoh_memakai_nama_kunci_usaha_yang_sama(): void
    {
        foreach (self::kunciUsaha() as $kunci) {
            $this->assertStringContainsString($kunci.'=', $this->contoh(), '.env.example harus memuat '.$kunci.'.');
            $this->assertStringContainsString($kunci.'=', $this->produksi(), '.env.production.example harus memuat '.$kunci.'.');
        }

        $this->assertStringNotContainsString('LAUNDRY_', $this->contoh());
    }
}
