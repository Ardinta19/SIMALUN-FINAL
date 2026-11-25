<?php

namespace Tests\Unit;

use App\Support\UploadLimit;
use PHPUnit\Framework\TestCase;

/**
 * Batas unggah dari `php.ini` dibaca apa adanya: "2M", "512K", "1G", angka
 * polos, atau kosong. Kalau salah baca, peringatan di checkout bisa
 * menakut-nakuti pelanggan padahal servernya sudah longgar.
 */
class UploadLimitTest extends TestCase
{
    public function test_singkatan_php_ini_dibaca_sebagai_megabyte(): void
    {
        $this->assertSame(2, UploadLimit::toMegabytes('2M'));
        $this->assertSame(30, UploadLimit::toMegabytes('30M'));
        $this->assertSame(1024, UploadLimit::toMegabytes('1G'));
        $this->assertSame(80, UploadLimit::toMegabytes('80M'));
        $this->assertSame(1, UploadLimit::toMegabytes('512K'));
        $this->assertSame(1, UploadLimit::toMegabytes('2M '), 'Spasi di ujung tidak mengubah nilai.');
        $this->assertSame(1, UploadLimit::toMegabytes('1MB'), 'Akhiran B boleh ditulis.');
    }

    public function test_nilai_tanpa_batas_menghasilkan_nol(): void
    {
        $this->assertSame(0, UploadLimit::toMegabytes(''));
        $this->assertSame(0, UploadLimit::toMegabytes('0'));
        $this->assertSame(0, UploadLimit::toMegabytes('-1'));
        $this->assertSame(0, UploadLimit::toMegabytes('tidak diketahui'));
    }

    public function test_angka_polos_dianggap_byte(): void
    {
        // php.ini mengizinkan angka tanpa satuan; satuannya byte.
        $this->assertSame(2, UploadLimit::toMegabytes('2097152'));
    }
}
