<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kolom yang diisi sendiri harus memberi contoh.
 *
 * Keluhan "jangan ada yang kosong" muncul dari formulir yang hanya berisi
 * kotak kosong: pelanggan dan admin tidak tahu format yang diharapkan
 * (tarif antar, alamat, ucapan). Setiap kolom teks yang tampil kosong kini
 * membawa placeholder berisi contoh nyata, bukan sekadar nama kolom.
 *
 * Cakupannya seluruh view (guest, auth, pelanggan, admin) — bukan daftar
 * halaman pilihan, supaya halaman baru ikut terjaga.
 *
 * Kolom yang memang tidak perlu contoh: jenis yang tidak diisi teks bebas
 * (`hidden`, `submit`, pilihan, berkas, tanggal/bulan, kata sandi), kolom
 * `readonly`, dan kolom yang sudah terisi nilai (`value="..."` atau isi
 * `<textarea>` dari data, bukan `old()`).
 */
class FormPlaceholderTest extends TestCase
{
    private const TIPE_DILEWATI = 'hidden|submit|button|reset|checkbox|radio|file|date|datetime-local|time|month|week|color|range|password|image';

    /** @return array<string, string> nama relatif => isi blade */
    private function semuaView(): array
    {
        $views = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $views[str_replace('\\', '/', $file->getRelativePathname())] = (string) File::get($file->getPathname());
        }

        ksort($views);

        return $views;
    }

    /** Siapkan teks supaya tag bisa dipotong dengan aman.
     *
     *  `{{ $unit->id }}` mengandung tanda `>` (operator `->`), sehingga regex
     *  tag sederhana memotong tag di tengah dan lolos dari pemeriksaan.
     *  Ekspresi Blade dan direktif diganti penanda lebih dulu.
     */
    private function tanpaEkspresiBlade(string $html): string
    {
        $html = preg_replace('/\{\{.*?\}\}/s', 'PHPX', $html);

        return preg_replace(
            '/@(?:if|elseif|unless|error|checked|selected|disabled|required|readonly|php|class|style)\s*\([^)]*\)/i',
            'DIRX',
            $html
        );
    }

    /** Kolom yang tampil kosong saat halaman pertama dibuka. */
    private function butuhContoh(string $tag): bool
    {
        if (preg_match('/type\s*=\s*[\'"](?:'.self::TIPE_DILEWATI.')[\'"]/i', $tag)) {
            return false;
        }

        if (preg_match('/\breadonly\b/i', $tag)) {
            return false;
        }

        if (! preg_match('/name\s*=\s*[\'"]([^\'"]+)/i', $tag)) {
            return false;
        }

        // Formulir ubah sudah terisi data lewat `value="..."`.
        if (stripos($tag, '<textarea') !== 0 && preg_match('/\bvalue\s*=\s*[\'"]/i', $tag)) {
            return false;
        }

        return true;
    }

    public function test_kolom_teks_selalu_memberi_contoh_isian(): void
    {
        $tanpaContoh = [];

        foreach ($this->semuaView() as $relative => $html) {
            preg_match_all('/<(?:input|textarea)\b[^>]*>/i', $this->tanpaEkspresiBlade($html), $found);

            foreach ($found[0] as $tag) {
                if (stripos($tag, 'placeholder') !== false || ! $this->butuhContoh($tag)) {
                    continue;
                }

                preg_match('/name\s*=\s*[\'"]([^\'"]+)/i', $tag, $name);
                $tanpaContoh[] = $relative.' → '.$name[1];
            }
        }

        $this->assertSame([], $tanpaContoh, 'Kolom teks tanpa contoh isian: '.implode(', ', $tanpaContoh));
    }

    /** Contoh isian harus benar-benar berupa contoh, bukan mengulang label.
     *
     *  Bentuk yang diterima: `Contoh: …` (isian) atau `Cari …` (pencarian).
     *  Kata sandi dikecualikan karena tidak boleh diberi contoh isi.
     */
    public function test_contoh_isian_memakai_kata_contoh(): void
    {
        $kurangJelas = [];

        foreach ($this->semuaView() as $relative => $html) {
            preg_match_all('/<(?:input|textarea)\b[^>]*>/i', $html, $found);

            foreach ($found[0] as $tag) {
                // Kata sandi memang tidak punya contoh isi; panduan singkat
                // seperti "Masukkan kata sandi Anda" adalah bentuk yang benar.
                if (preg_match('/type\s*=\s*[\'"]password[\'"]/i', $tag)) {
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

                // Satu bentuk untuk semua: `Contoh: …` untuk isian,
                // `Cari …` untuk kotak pencarian. Selain itu berarti masih
                // berupa perintah/label (“Masukkan…”, “Warna pagar, dll.”).
                if (! preg_match('/^(contoh|cari)\b/i', $trimmed)) {
                    $kurangJelas[] = $relative.' → '.$trimmed;
                }
            }
        }

        $this->assertSame([], $kurangJelas, 'Placeholder yang bukan contoh isian: '.implode(', ', $kurangJelas));
    }

    /** Satu tag isian hanya boleh punya satu `placeholder`.
     *
     *  Audit 2026-11-25 menemukan dua isian di profil pelanggan yang memasang
     *  `placeholder` dua kali (`Contoh: 0812 3456 7890` lalu `08xxxxxxxxxx`).
     *  Peramban memakai yang terakhir, sehingga contoh isian yang benar hilang
     *  tanpa jejak — dan tes placeholder di kelas ini tetap hijau karena hanya
     *  membaca atribut pertama. Tes ini memindai seluruh tag.
     */
    public function test_tidak_ada_atribut_placeholder_ganda(): void
    {
        $ganda = [];

        foreach ($this->semuaView() as $relative => $html) {
            preg_match_all('/<(?:input|textarea)\b[^>]*>/i', $html, $found);

            foreach ($found[0] as $tag) {
                $jumlah = preg_match_all('/\bplaceholder\s*=/i', $tag);

                if ($jumlah > 1) {
                    $ganda[] = $relative.' → '.$jumlah.' placeholder';
                }
            }
        }

        $this->assertSame([], $ganda, 'Atribut placeholder ganda: '.implode(', ', $ganda));
    }
}
