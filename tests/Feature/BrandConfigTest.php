<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Data usaha punya satu pintu masuk.
 *
 * Modul laundry meninggalkan beberapa jalur lama untuk membaca data usaha:
 * singleton `laundry` yang dibagikan ke semua view, tiga direktif Blade
 * (`@laundryName`, `@laundryAddress`, `@laundryPhone`), dan pembungkus
 * statis yang tidak pernah dipanggil. Semuanya dihapus; yang tersisa hanya
 * `App\Support\Laundry` dan pemakaiannya lewat view.
 *
 * Tes ini menjaga dua hal: tidak ada lagi jalur mati yang didaftarkan, dan
 * setiap pembungkus statis di `Laundry` benar-benar dipakai di suatu tempat.
 */
class BrandConfigTest extends TestCase
{
    /** @return array<int, string> */
    private function viewFiles(): array
    {
        $out = [];
        $root = resource_path('views');
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $out[] = $file->getPathname();
            }
        }
        sort($out);

        return $out;
    }

    public function test_tidak_ada_jalur_lama_data_usaha(): void
    {
        $provider = (string) file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringNotContainsString("singleton('laundry'", $provider);
        $this->assertStringNotContainsString("View::share('laundry'", $provider);
        $this->assertStringNotContainsString("Blade::directive('laundry", $provider);

        foreach ($this->viewFiles() as $path) {
            $isi = (string) file_get_contents($path);
            $nama = str_replace(base_path().'/', '', $path);

            $this->assertStringNotContainsString('@laundryName', $isi, $nama.' memakai direktif lama.');
            $this->assertStringNotContainsString('@laundryAddress', $isi, $nama.' memakai direktif lama.');
            $this->assertStringNotContainsString('@laundryPhone', $isi, $nama.' memakai direktif lama.');
            $this->assertStringNotContainsString('$laundry->', $isi, $nama.' memakai variabel bersama lama.');
        }
    }

    public function test_setiap_pembungkus_laundry_dipakai(): void
    {
        $kelas = (string) file_get_contents(app_path('Support/Laundry.php'));
        preg_match_all('/public static function ([a-zA-Z0-9_]+)\(/', $kelas, $metode);
        $metode = $metode[1];
        $this->assertNotEmpty($metode, 'Metode Laundry tidak terbaca.');

        $sumber = '';
        foreach (['app', 'resources'] as $folder) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($folder), \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                if (str_ends_with($file->getPathname(), 'Support/Laundry.php')) {
                    continue;
                }
                $sumber .= (string) file_get_contents($file->getPathname());
            }
        }

        $tidakDipakai = [];
        foreach ($metode as $nama) {
            if (! str_contains($sumber, 'Laundry::'.$nama.'(')) {
                $tidakDipakai[] = $nama;
            }
        }

        $this->assertSame([], $tidakDipakai, 'Pembungkus tanpa pemakai: '.implode(', ', $tidakDipakai));
    }
}
