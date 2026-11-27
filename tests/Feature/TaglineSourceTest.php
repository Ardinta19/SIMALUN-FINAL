<?php

namespace Tests\Feature;

use App\Support\Laundry;
use Tests\TestCase;

/**
 * Putaran 35 — tagline satu sumber.
 *
 * Empat kalimat berbeda pernah hidup berdampingan di header, splash login, dan
 * email. Semuanya sekarang membaca `BUSINESS_TAGLINE` lewat
 * `App\Support\Laundry::tagline()`, sehingga mengganti tagline cukup satu
 * baris `.env`. Tes ini menjaga agar tidak ada view yang kembali menulis
 * kalimatnya sendiri.
 */
class TaglineSourceTest extends TestCase
{
    public function test_tagline_diambil_dari_config(): void
    {
        $this->assertSame((string) config('laundry.tagline'), Laundry::tagline());
        $this->assertNotSame('', Laundry::tagline());
    }

    public function test_tidak_ada_view_yang_menulis_tagline_sendiri(): void
    {
        $views = [
            'catalog/layout.blade.php',
            'catalog/customer-navigation.blade.php',
            'auth/login.blade.php',
            'auth/forgot-password.blade.php',
            'rental/public-like.blade.php',
        ];

        foreach ($views as $view) {
            $contents = (string) file_get_contents(resource_path('views/'.$view));
            $this->assertStringContainsString('Laundry::tagline()', $contents, $view.' harus memakai tagline dari config.');
            $this->assertStringNotContainsString('Papan bunga untuk momen istimewa', $contents, $view.' masih menulis tagline sendiri.');
            $this->assertStringNotContainsString('Rangkaian bunga untuk momen yang kamu tunggu', $contents, $view.' masih menulis tagline sendiri.');
        }
    }
}
