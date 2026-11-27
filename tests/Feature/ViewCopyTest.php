<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Bentuk kata yang sudah diputuskan tidak boleh muncul lagi di antarmuka.
 *
 * `UiDefaultsTest` memeriksa pesan galat di controller; berkas Blade dan
 * perintah terminal tidak tersentuh di sana. Karena itu empat teks "No. HP"
 * masih tertinggal di layar masuk, dua formulir alamat, dan formulir profil,
 * padahal keputusannya "nomor HP". Pemeriksaan ini menutup celah tersebut.
 */
class ViewCopyTest extends TestCase
{
    /**
     * Semua view, berapa pun dalamnya (`roles/admin/audit/index.blade.php`
     * sudah tiga tingkat). `glob()` dengan pola bertingkat melewatkan berkas
     * yang lebih dalam, jadi ditelusuri rekursif.
     *
     * @return array<int, string>
     */
    private function viewFiles(): array
    {
        $root = resource_path('views');
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = str_replace('\\', '/', $file->getPathname());
            }
        }
        sort($files);

        return $files;
    }

    /** @return array<int, string> */
    private function consoleFiles(): array
    {
        $files = glob(app_path('Console/Commands/*.php')) ?: [];
        sort($files);

        return $files;
    }

    public function test_berkas_antarmuka_dapat_dipindai(): void
    {
        $this->assertTrue(count($this->viewFiles()) > 40, 'Pemindai tidak menemukan berkas Blade.');
        $this->assertTrue(count($this->consoleFiles()) > 0, 'Pemindai tidak menemukan perintah terminal.');
    }

    public function test_teks_antarmuka_tidak_memakai_bentuk_terlarang(): void
    {
        // Bentuk baku: "nomor HP" (bukan "No. HP"), sapaan "kamu" (bukan
        // "Anda"), dan ejaan "silakan" (bukan "silahkan").
        $terlarang = [
            '/\bNo\.?\s?HP\b/' => 'memakai "No. HP" (pakai "nomor HP")',
            '/\bAnda\b/' => 'menyapa "Anda" (pakai "kamu")',
            '/\bSilahkan\b/' => 'salah eja "Silahkan" (pakai "Silakan")',
        ];

        $masalah = [];
        $berkas = array_merge($this->viewFiles(), $this->consoleFiles(), glob(base_path('lang/id/*.php')) ?: []);

        foreach ($berkas as $path) {
            $relatif = str_replace(base_path().'/', '', $path);
            $baris = explode("\n", (string) file_get_contents($path));

            foreach ($baris as $nomor => $isi) {
                foreach ($terlarang as $pola => $keterangan) {
                    if (preg_match($pola, $isi)) {
                        $masalah[] = $relatif.':'.($nomor + 1).' '.$keterangan;
                    }
                }
            }
        }

        $masalah = array_values(array_unique($masalah));
        $this->assertSame([], $masalah, implode(' | ', $masalah));
    }
}
