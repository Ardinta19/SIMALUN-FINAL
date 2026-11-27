<?php

namespace Tests\Feature;

use App\Support\InputSanitizer;
use Tests\TestCase;

/**
 * Pembersih masukan tidak boleh mengubah kalimat biasa.
 *
 * Bug yang dijaga di sini: semua nilai dulu dilewatkan `strip_tags()` dan
 * pembersih atribut `on…=`, sehingga "Papan Bunga <3 untuk Ibu Sari"
 * terpotong menjadi "Papan Bunga" dan catatan "ongkir=25000" hilang
 * seluruhnya. Nilai tanpa tag kini dikembalikan apa adanya.
 */
class InputSanitizerTest extends TestCase
{
    /**
     * Harness PHP-WASM tidak memuat autoloader Composer, jadi berkas kelas
     * dimuat manual bila belum ada. Di `php artisan test` kelas sudah
     * termuat otomatis dan baris ini tidak berbuat apa-apa.
     */
    private function sanitizer(): string
    {
        if (! class_exists(InputSanitizer::class)) {
            require_once dirname(__DIR__, 2).'/app/Support/InputSanitizer.php';
        }

        return InputSanitizer::class;
    }

    public function test_teks_biasa_dikembalikan_apa_adanya(): void
    {
        $clean = $this->sanitizer();

        $samples = [
            'Ongkos kirim 25.000 dibayar tunai',
            'ongkir=25000 ditanggung toko',
            'Papan Bunga <3 untuk Ibu Sari',
            'Alamat: Jl. Merdeka No. 12, RT 03',
            'Papan model online=baru',
            'Kakak <Budi> yang mengambil papan',
            'Catatan dua baris
dengan baris kedua',
        ];

        foreach ($samples as $sample) {
            $this->assertSame($sample, $clean::clean($sample), 'Teks biasa berubah: '.$sample);
        }
    }

    public function test_spasi_tepi_dipangkas_dan_byte_nol_dibuang(): void
    {
        $clean = $this->sanitizer();

        $this->assertSame('Catatan', $clean::clean("  Catatan  "));
        $this->assertSame('ab', $clean::clean("a\x00b"));
    }

    public function test_tag_dan_atribut_berbahaya_tetap_dibersihkan(): void
    {
        $clean = $this->sanitizer();

        $this->assertSame('Halo', $clean::clean('<script>alert(1)</script>Halo'));
        $this->assertSame('Selamat', $clean::clean('<img src=x onerror=alert(1)>Selamat'));
        $this->assertSame('tebal biasa', $clean::clean('<b>tebal</b> biasa'));
        $this->assertSame('Halo', $clean::clean('<iframe src="https://jahat.example"></iframe>Halo'));
        $this->assertSame('Halo', $clean::clean('<a href="javascript:alert(1)">Halo</a>'));
    }

    public function test_spasi_ganda_sisa_tag_dirapikan(): void
    {
        $clean = $this->sanitizer();

        $this->assertSame('satu dua', $clean::clean("<p>satu</p>   \n  dua"));
    }
}
