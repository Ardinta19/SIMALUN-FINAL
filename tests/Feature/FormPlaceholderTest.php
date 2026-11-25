<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kolom yang diisi sendiri harus memberi contoh.
 *
 * Keluhan "jangan ada yang kosong" muncul dari formulir yang hanya berisi
 * kotak kosong: pelanggan dan admin tidak tahu format yang diharapkan
 * (tarif antar, alamat, ucapan). Setiap kolom teks kini membawa placeholder
 * berisi contoh nyata, bukan sekadar nama kolom.
 *
 * Kolom yang memang tidak perlu contoh: `hidden`, `submit`, pilihan
 * (checkbox/radio/select), pemilih berkas, pemilih tanggal, dan kolom jumlah
 * yang sudah bernilai tetap.
 */
class FormPlaceholderTest extends TestCase
{
    /** Formulir pelanggan + admin yang berisi isian teks bebas. */
    private const FORMS = [
        'auth/forgot-password.blade.php',
        'catalog/product-fields.blade.php',
        'rental/addons.blade.php',
        'rental/checkout.blade.php',
        'rental/index.blade.php',
        'rental/payment-settings.blade.php',
        'rental/progress.blade.php',
        'rental/unit.blade.php',
        'rental/universities.blade.php',
        'rental/vouchers.blade.php',
        'roles/admin/profile_edit.blade.php',
        'roles/customer/addresses/create.blade.php',
        'roles/customer/addresses/edit.blade.php',
        'roles/customer/profile/edit.blade.php',
    ];

    public function test_kolom_teks_selalu_memberi_contoh_isian(): void
    {
        $tanpaContoh = [];

        foreach (self::FORMS as $relative) {
            $path = resource_path('views/'.$relative);
            $this->assertFileExists($path);

            $html = (string) File::get($path);
            preg_match_all('/<(?:input|textarea)\b[^>]*>/i', $html, $found);

            foreach ($found[0] as $tag) {
                // Jenis kolom yang tidak diisi teks bebas.
                if (preg_match('/type\s*=\s*["\'](?:hidden|submit|button|checkbox|radio|file|date|datetime-local|time|month|week|color|range|password)["\']/i', $tag)) {
                    continue;
                }

                if (stripos($tag, 'placeholder') !== false) {
                    continue;
                }

                if (! preg_match('/name\s*=\s*["\']([^"\']+)/i', $tag, $name)) {
                    continue;
                }

                $tanpaContoh[] = $relative.' → '.$name[1];
            }
        }

        $this->assertSame([], $tanpaContoh, 'Kolom teks tanpa contoh isian: '.implode(', ', $tanpaContoh));
    }

    /** Contoh isian harus benar-benar berupa contoh, bukan mengulang label. */
    public function test_contoh_isian_memakai_kata_contoh(): void
    {
        $kurangJelas = [];

        foreach (self::FORMS as $relative) {
            $html = (string) File::get(resource_path('views/'.$relative));
            preg_match_all('/<(?:input|textarea)\b[^>]*>/i', $html, $found);

            foreach ($found[0] as $tag) {
                // Kata sandi memang tidak punya contoh isi; panduan singkat
                // seperti "Masukkan kata sandi Anda" adalah bentuk yang benar.
                if (preg_match('/type\s*=\s*["\']password["\']/i', $tag)) {
                    continue;
                }

                if (! preg_match('/placeholder\s*=\s*"([^"]*)"/i', $tag, $match)) {
                    continue;
                }

                $trimmed = trim($match[1]);

                if ($trimmed === '') {
                    $kurangJelas[] = $relative.' → kosong';
                    continue;
                }

                // Cari contoh yang berupa perintah, bukan contoh isi.
                if (preg_match('/^(masukkan|isi|ketik|tulis)\b/i', $trimmed)) {
                    $kurangJelas[] = $relative.' → '.$trimmed;
                }
            }
        }

        $this->assertSame([], $kurangJelas, 'Placeholder yang bukan contoh isian: '.implode(', ', $kurangJelas));
    }
}
