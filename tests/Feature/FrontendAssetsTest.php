<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Langkah build harus sejalan dengan cara halaman memuat aset.
 *
 * Selama ini `composer setup` menjalankan `npm install` + `npm run build`,
 * padahal tidak satu view pun memanggil `@vite` — seluruh halaman memuat
 * `asset('css/…')` dan `asset('js/…')` dari `public/`. Akibatnya setiap
 * pemasangan menghabiskan waktu membangun `public/build` yang tidak pernah
 * diminta browser. Langkah itu dihapus dari `composer setup` dan
 * `composer dev`, `resources/js/splash.js` (satu-satunya pemakai GSAP)
 * dibuang.
 *
 * Tes ini menjaga kedua arah: kalau nanti ada view yang mulai memakai
 * `@vite`, langkah build wajib dikembalikan; kalau semua view tetap memakai
 * `asset()`, langkah build tidak boleh muncul lagi.
 */
class FrontendAssetsTest extends TestCase
{
    /** @return array<int, string> */
    private function views(): array
    {
        $out = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $out[$file->getPathname()] = (string) file_get_contents($file->getPathname());
            }
        }

        return $out;
    }

    private function composer(): array
    {
        return json_decode((string) file_get_contents(base_path('composer.json')), true)['scripts'];
    }

    public function test_langkah_build_selaras_dengan_pemakaian_vite(): void
    {
        $pakaiVite = [];
        foreach ($this->views() as $path => $isi) {
            if (str_contains($isi, '@vite(')) {
                $pakaiVite[] = str_replace(base_path().'/', '', $path);
            }
        }

        $scripts = $this->composer();
        $setup = implode(' ', $scripts['setup']);
        $dev = implode(' ', $scripts['dev']);
        $adaBuild = str_contains($setup, 'npm run build');
        $adaNpmInstall = str_contains($setup, 'npm install');

        if ($pakaiVite === []) {
            $this->assertFalse($adaBuild, 'Tidak ada view yang memakai @vite, jadi setup tidak perlu npm run build.');
            $this->assertFalse($adaNpmInstall, 'Tidak ada view yang memakai @vite, jadi setup tidak perlu npm install.');
            $this->assertStringNotContainsString('npm run dev', $dev, 'Tanpa pemakai @vite, vite dev server tidak perlu dijalankan.');
        } else {
            $this->assertTrue($adaBuild, 'View memakai @vite tetapi composer setup tidak membangun aset.');
        }
    }

    public function test_semua_berkas_aset_public_dipakai(): void
    {
        $sumber = implode("\n", array_values($this->views()));

        foreach ([public_path('js'), public_path('css')] as $folder) {
            foreach (glob($folder.'/*') as $berkas) {
                $nama = basename($berkas);
                $this->assertStringContainsString($nama, $sumber, 'Berkas aset tidak dipakai: '.$nama);
            }
        }
    }
}
