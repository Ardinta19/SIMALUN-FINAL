<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Seluruh rute tulis keranjang memodifikasi isi sesi, dan sesi disimpan di
 * tabel `sessions` (bawaan `SESSION_DRIVER=database`). Tanpa pembatas,
 * satu skrip bisa memaksa penulisan sesi berkali-kali per detik — apalagi
 * sekarang tombol −/+ bisa ditekan berulang.
 */
class CartThrottleTest extends TestCase
{
    public function test_semua_rute_tulis_keranjang_dibatasi(): void
    {
        $rute = File::get(base_path('routes/web.php'));

        foreach ([
            "Route::post('/keranjang/{product}'",
            "Route::post('/keranjang/unit/{item}/tambah'",
            "Route::post('/keranjang/unit/{item}/kurangi'",
            "Route::post('/keranjang/unit/{item}/hapus-semua'",
            "Route::delete('/keranjang/{item}'",
        ] as $awalan) {
            $posisi = strpos($rute, $awalan);
            $this->assertNotFalse($posisi, 'Rute tidak ditemukan: '.$awalan);

            $this->assertStringContainsString(
                'throttle:30,1',
                substr($rute, $posisi, 200),
                'Rute keranjang harus dibatasi 30 permintaan per menit: '.$awalan
            );
        }
    }
}
