<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Nama rute dan nama view yang ditulis di Blade/controller harus benar-benar ada.
 *
 * Salah ketik satu huruf pada `route('admin.rental.orders')` atau
 * `@include('layouts.component._icon')` baru terasa saat halaman dibuka, dan
 * hasilnya 500 — bukan pesan yang bisa dimengerti pengguna. Pemeriksaan ini
 * mencocokkan semua pemanggilan literal dengan daftar di `routes/web.php` dan
 * berkas di `resources/views`.
 *
 * Rute dengan awalan grup (`admin.`, `customer.`) dibentuk dari
 * `->prefix('admin')->name('admin.')`, jadi awalan itu ikut dikumpulkan.
 */
class RouteReferenceTest extends TestCase
{
    /** @return array<int, string> */
    private function phpFiles(): array
    {
        $out = [];
        foreach (['app', 'resources/views', 'public/js'] as $dir) {
            $root = base_path($dir);
            if (! is_dir($root)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                if (preg_match('/\.(php|blade\.php|js)$/', $file->getFilename())) {
                    $out[] = str_replace('\\', '/', $file->getPathname());
                }
            }
        }
        sort($out);

        return $out;
    }

    /** @return array{0: array<int,string>, 1: array<int,string>} */
    private function routeNames(): array
    {
        $source = '';
        foreach (glob(base_path('routes/*.php')) ?: [] as $file) {
            $source .= file_get_contents($file);
        }

        $names = [];
        preg_match_all("/->name\\('([^']+)'\\)/", $source, $cocok);
        $names = $cocok[1];

        $prefixes = [];
        foreach ($names as $name) {
            if (str_ends_with($name, '.')) {
                $prefixes[] = $name;
            }
        }

        return [$names, $prefixes];
    }

    private function routeExists(string $name, array $names, array $prefixes): bool
    {
        if (in_array($name, $names, true)) {
            return true;
        }
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix) && in_array(substr($name, strlen($prefix)), $names, true)) {
                return true;
            }
        }

        return false;
    }

    public function test_setiap_nama_rute_yang_dipanggil_benar_benar_ada(): void
    {
        [$names, $prefixes] = $this->routeNames();
        $this->assertTrue(count($names) > 80, 'Daftar rute tidak terbaca dengan benar.');

        $masalah = [];
        $dipakai = 0;

        foreach ($this->phpFiles() as $path) {
            $source = (string) file_get_contents($path);
            preg_match_all("/route\\('([a-z][a-z0-9_.\\-]*)'/", $source, $cocok);

            foreach ($cocok[1] as $nama) {
                $dipakai++;
                if (! $this->routeExists($nama, $names, $prefixes)) {
                    $masalah[] = str_replace(base_path().'/', '', $path).' memanggil rute '.$nama;
                }
            }
        }

        $this->assertTrue($dipakai > 100, 'Pemanggilan route() tidak terbaca dengan benar.');
        $this->assertSame([], array_values(array_unique($masalah)), implode(' | ', array_values(array_unique($masalah))));
    }

    public function test_setiap_view_yang_dirujuk_benar_benar_ada(): void
    {
        $masalah = [];
        $dirujuk = 0;

        foreach ($this->phpFiles() as $path) {
            $source = (string) file_get_contents($path);

            preg_match_all("/@(?:include|extends|each)\\('([a-zA-Z0-9_.\\-]+)'/", $source, $blade, PREG_SET_ORDER);
            preg_match_all("/view\\('([a-zA-Z0-9_.\\-]+)'/", $source, $kontroler, PREG_SET_ORDER);

            foreach (array_merge($blade, $kontroler) as $item) {
                $nama = $item[1];
                if ($nama === '' || str_contains($nama, '$')) {
                    continue;
                }

                $dirujuk++;
                $file = resource_path('views/'.str_replace('.', '/', $nama).'.blade.php');
                if (! is_file($file)) {
                    $masalah[] = str_replace(base_path().'/', '', $path).' merujuk view '.$nama;
                }
            }
        }

        $this->assertTrue($dirujuk > 100, 'Rujukan view tidak terbaca dengan benar.');
        $this->assertSame([], array_values(array_unique($masalah)), implode(' | ', array_values(array_unique($masalah))));
    }
}
