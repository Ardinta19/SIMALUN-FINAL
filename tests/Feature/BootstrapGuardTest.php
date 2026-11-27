<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `bootstrap/app.php` dievaluasi sebelum bootstrapper `LoadConfiguration` dan
 * `LoadEnvironmentVariables` berjalan. Memanggil `config()`, `env()`, atau
 * `app()` di dalamnya — termasuk di dalam closure `withMiddleware` — membuat
 * aplikasi gagal total:
 *
 *   Uncaught ReflectionException: Class "config" does not exist
 *
 * Gejalanya menyesatkan karena tidak menyebut berkas mana pun, dan yang mati
 * justru semua perintah artisan (termasuk `migrate` dan `test`).
 */
class BootstrapGuardTest extends TestCase
{
    /** Buang komentar supaya penjelasan di dalam kode tidak ikut diperiksa. */
    private function tanpaKomentar(string $isi): string
    {
        $isi = (string) preg_replace('#/\*.*?\*/#s', '', $isi);

        return (string) preg_replace('#(?<!:)//[^\n]*#', '', $isi);
    }

    public function test_bootstrap_tidak_memakai_helper_yang_belum_tersedia(): void
    {
        $kode = $this->tanpaKomentar(File::get(base_path('bootstrap/app.php')));

        foreach (['config(', 'env(', 'app('] as $terlarang) {
            $this->assertStringNotContainsString(
                $terlarang,
                $kode,
                'bootstrap/app.php dipanggil sebelum konfigurasi dimuat; "'.$terlarang.'" akan menggagalkan seluruh aplikasi.'
            );
        }
    }

    public function test_proxy_tepercaya_dikonfigurasi_di_provider(): void
    {
        $provider = $this->tanpaKomentar(File::get(app_path('Providers/AppServiceProvider.php')));

        $this->assertStringContainsString(
            'TrustProxiesMiddleware::at(config(\'app.trusted_proxies\'',
            $provider,
            'Proxy tepercaya harus dipasang di provider tempat config() sudah tersedia.'
        );

        $bootstrap = $this->tanpaKomentar(File::get(base_path('bootstrap/app.php')));
        $this->assertStringNotContainsString('trustProxies(', $bootstrap, 'trustProxies() di bootstrap/app.php butuh nilai config yang belum ada.');
    }

    /**
     * Bukti bahwa nilainya benar-benar terpasang saat aplikasi hidup:
     * `TrustProxies::at()` mengisi properti statis yang dibaca middleware.
     */
    public function test_nilai_proxy_tepercaya_terpasang_setelah_aplikasi_boot(): void
    {
        $properti = new \ReflectionProperty(TrustProxies::class, 'alwaysTrustProxies');
        $nilai = $properti->getValue();

        $this->assertSame(config('app.trusted_proxies'), $nilai);
        $this->assertNotEmpty($nilai);
    }
}
