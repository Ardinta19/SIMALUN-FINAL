<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `php artisan route:cache` adalah langkah wajib saat memasang di server,
 * tetapi satu rute ber-closure membuat perintah itu gagal total:
 *
 *   Unable to prepare route [login] for serialization. Uses Closure.
 *
 * Kegagalannya tidak kelihatan di halaman mana pun — aplikasi tetap jalan,
 * hanya saja daftar rute harus disusun ulang pada setiap permintaan.
 */
class RouteCacheTest extends TestCase
{
    public function test_tidak_ada_rute_aksi_berupa_closure(): void
    {
        $masalah = [];

        foreach (File::files(base_path('routes')) as $berkas) {
            $isi = File::get($berkas->getPathname());

            // Hanya closure pada *aksi* rute yang menggagalkan route:cache.
            // Closure milik `->group(function () { ... })` aman karena tidak
            // ikut diserialisasi.
            if (preg_match_all('/Route::\w+\([^;]*?fn\s*\(/', $isi, $cocok)) {
                foreach ($cocok[0] as $potongan) {
                    $masalah[] = $berkas->getFilename().': '.trim(preg_replace('/\s+/', ' ', $potongan));
                }
            }

            if (preg_match_all('/Route::\w+\([^;]*?function\s*\(/', $isi, $cocok2)) {
                foreach ($cocok2[0] as $potongan) {
                    if (str_contains($potongan, 'group')) {
                        continue;
                    }
                    $masalah[] = $berkas->getFilename().': '.trim(preg_replace('/\s+/', ' ', $potongan));
                }
            }
        }

        $this->assertSame([], $masalah, 'Rute ber-closure menggagalkan route:cache: '.implode(' | ', $masalah));
    }

    public function test_rute_dapat_di_cache(): void
    {
        try {
            $this->artisan('route:cache')->assertExitCode(0);
        } finally {
            $this->artisan('route:clear');
        }
    }
}
