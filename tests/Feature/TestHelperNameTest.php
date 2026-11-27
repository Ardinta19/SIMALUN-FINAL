<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * PHPUnit memuat seluruh berkas tes lebih dulu, jadi satu helper privat yang
 * kebetulan bernama sama dengan metode `Illuminate\Foundation\Testing\TestCase`
 * membuat seluruh suite berhenti dengan "Fatal error: Access level ... must be
 * protected". Galat itu tidak terlihat di pemeriksaan sintaks dan tidak bisa
 * ditangkap tes lain karena suite belum sempat berjalan. Tes ini memeriksa nama
 * yang paling mudah terpakai ulang (view, get, post, ...) sebelum itu terjadi.
 */
class TestHelperNameTest extends TestCase
{
    /** @return array<int, string> */
    private function methodBawaan(): array
    {
        return [
            'view', 'blade', 'get', 'post', 'put', 'patch', 'delete', 'call', 'json',
            'artisan', 'seed', 'actingAs', 'be', 'followingRedirects', 'withoutMiddleware',
        ];
    }

    public function test_helper_privat_tidak_menabrak_metode_testcase(): void
    {
        $pelanggaran = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('tests'), \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $berkas) {
            if (! $berkas->isFile() || ! str_ends_with($berkas->getFilename(), '.php')) {
                continue;
            }

            $isi = (string) file_get_contents($berkas->getPathname());
            foreach ($this->methodBawaan() as $nama) {
                if (preg_match('/private function '.preg_quote($nama, '/').'\s*\(/', $isi) === 1) {
                    $pelanggaran[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $berkas->getPathname()).' → '.$nama.'()';
                }
            }
        }

        $this->assertSame([], $pelanggaran, 'Ganti nama helper privat ini agar tidak bentrok dengan TestCase: '.implode(', ', $pelanggaran));
    }
}
