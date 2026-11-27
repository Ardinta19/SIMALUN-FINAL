<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Alias pendek berbahasa Indonesia untuk halaman masuk dan daftar.
 *
 * Pengguna sering mengetik `/masuk` atau `/daftar`; tanpa rute ini keduanya
 * berakhir 404 padahal halamannya ada. Alias hanya mengalihkan, tidak
 * menggandakan logika autentikasi.
 */
class RouteAliasTest extends TestCase
{
    public function test_masuk_dan_daftar_mengarah_ke_halaman_yang_benar(): void
    {
        $this->get('/masuk')->assertRedirect('/login');
        $this->get('/daftar')->assertRedirect('/register');
    }
}
