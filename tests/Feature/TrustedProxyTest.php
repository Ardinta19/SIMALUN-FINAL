<?php

namespace Tests\Feature;

use App\Support\TrustedProxies;
use Tests\TestCase;

/**
 * Proxy tepercaya bisa diatur tanpa menyentuh kode.
 *
 * `bootstrap/app.php` dulu menulis `trustProxies(at: '*')` langsung di kode.
 * Nilai itu dipertahankan sebagai bawaan (hosting menerminasi TLS di proxy),
 * tetapi sekarang bisa diubah lewat `TRUSTED_PROXIES`, dan alasannya
 * didokumentasikan supaya tidak "diam-diam" jadi celah pemalsuan IP pada
 * pembatasan percobaan masuk.
 */
class TrustedProxyTest extends TestCase
{
    /**
     * Harness PHP-WASM tidak memuat autoloader Composer; di `php artisan test`
     * kelas sudah termuat otomatis dan baris ini tidak berbuat apa-apa.
     */
    private function muatKelas(): void
    {
        if (! class_exists(TrustedProxies::class)) {
            require_once dirname(__DIR__, 2).'/app/Support/TrustedProxies.php';
        }
    }

    public function test_daftar_proxy_diurai_dari_teks_terpisah_koma(): void
    {
        $this->muatKelas();

        $this->assertSame(['10.0.0.1', '10.0.0.2'], TrustedProxies::parse('10.0.0.1, 10.0.0.2'));
        $this->assertSame(['127.0.0.1', '::1'], TrustedProxies::parse(' 127.0.0.1 , ::1 '));
    }

    public function test_nilai_kosong_memakai_bawaan_yang_sama_dengan_sebelumnya(): void
    {
        $this->muatKelas();

        $this->assertSame(['*'], TrustedProxies::parse(null));
        $this->assertSame(['*'], TrustedProxies::parse(''));
        $this->assertSame(['*'], TrustedProxies::parse(' , , '));
        $this->assertSame(['*'], TrustedProxies::parse('*'));
    }

    public function test_pengaturan_proxy_tidak_lagi_menulis_daftar_di_kode(): void
    {
        // `bootstrap/app.php` dievaluasi sebelum config dimuat, jadi
        // `trustProxies()` tidak boleh dipanggil di sana (lihat
        // BootstrapGuardTest: pernah membuat semua perintah artisan gagal).
        $bootstrap = (string) preg_replace(
            '#(?<!:)//[^\n]*#',
            '',
            (string) file_get_contents(base_path('bootstrap/app.php'))
        );
        $this->assertStringNotContainsString('trustProxies', $bootstrap);
        $this->assertStringNotContainsString("at: '*'", $bootstrap);

        $provider = (string) file_get_contents(app_path('Providers/AppServiceProvider.php'));
        $this->assertStringContainsString("TrustProxiesMiddleware::at(config('app.trusted_proxies'", $provider);

        $config = (string) file_get_contents(base_path('config/app.php'));
        $this->assertStringContainsString("TrustedProxies::parse(env('TRUSTED_PROXIES'))", $config);
    }
}
