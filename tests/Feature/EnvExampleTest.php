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
}
