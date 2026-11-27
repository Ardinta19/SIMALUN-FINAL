<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Putaran 36 — checkout bertahap.
 *
 * Halaman checkout sebelumnya satu formulir panjang: enam bagian, dua puluh
 * lebih isian, dan paragraf penjelasan di antara isian. Tes ini mengunci
 * struktur penggantinya (tahapan, bilah kemajuan, daftar kesiapan), sifat
 * "tetap bekerja tanpa JavaScript", dan dua keputusan pemilik usaha: teks
 * panjang diganti kalimat pendek, dan kolom total harga yang berubah setiap
 * kali jumlah disentuh dicabut dari katalog.
 *
 * Perilaku runtime (pindah bagian, hitungan isian, penjagaan kirim) diuji di
 * jsdom lewat `/tmp/wasm-php/checkout-steps-jsdom.cjs`; yang dijaga di sini
 * adalah hal-hal yang mudah hilang saat markup disunting.
 */
class CheckoutFlowTest extends TestCase
{
    /** Nama sengaja tidak `view()`: `Illuminate\Foundation\Testing\TestCase`
     *  sudah punya `view()` dan menurunkannya akan bentrok saat kelas dimuat. */
    private function checkoutView(): string
    {
        return (string) file_get_contents(resource_path('views/rental/checkout.blade.php'));
    }

    private function checkoutScript(): string
    {
        return (string) file_get_contents(public_path('js/berkah-checkout.js'));
    }

    public function test_checkout_memakai_tahapan_bilah_kemajuan_dan_daftar_kesiapan(): void
    {
        $view = $this->checkoutView();

        foreach ([
            'data-bk-co',
            'data-co-progress',
            'data-co-progress-label',
            'data-co-progress-pct',
            'data-co-progressbar',
            'data-co-progress-fill',
            'aria-label="Tahapan penyewaan"',
            'data-co-stage="unit"',
            'data-co-stage="terms"',
            'data-co-stage="send"',
            'data-co-unit-state',
            'data-co-next',
            'data-co-prev',
            'data-co-checklist',
            'data-co-check-unit',
            'data-checkout-submit',
            'data-checkout-status',
        ] as $hook) {
            $this->assertStringContainsString($hook, $view, 'Checkout kehilangan kait: '.$hook);
        }

        // Tiga bagian per papan, lengkap dengan tab dan panel yang berpasangan.
        foreach ([1, 2, 3] as $step) {
            $this->assertStringContainsString('data-co-tab="'.$step.'"', $view);
            $this->assertStringContainsString('data-co-panel="'.$step.'"', $view);
            $this->assertStringContainsString('checkout-{{ $unitNumber }}-tab-'.$step.'"', $view);
        }

        // Judul bagian tetap ada: dipakai kalimat pemandu di keranjang dan tes lama.
        $this->assertStringContainsString('>Detail penyewaan</h2>', $view);

        // Kepala halaman menjanjikan tiga hal yang benar-benar dilakukan sistem.
        $this->assertStringContainsString('Tanggal ditahan 24 jam', $view);
        $this->assertStringContainsString('Admin mengonfirmasi jadwal', $view);
        $this->assertStringContainsString('Bukti bayar hanya untuk admin', $view);
    }

    public function test_checkout_tetap_bisa_dipakai_tanpa_javascript(): void
    {
        $view = $this->checkoutView();
        $script = $this->checkoutScript();
        $css = (string) file_get_contents(public_path('css/berkah-experience.css'));

        // Panel tidak disembunyikan di server; hanya skrip yang menyembunyikan
        // bagian yang tidak aktif, setelah menandai wadahnya.
        $this->assertStringNotContainsString('data-co-panel="1" hidden', $view);
        $this->assertStringContainsString("root.setAttribute('data-co-ready', '')", $script);
        $this->assertStringContainsString('.bk-co[data-co-ready] .bk-co-panel:not([data-co-active])', $css);

        // Tanpa skrip pun isian wajib tetap diminta peramban.
        $this->assertStringContainsString('required', $view);
        $this->assertStringContainsString('name="terms"', $view);

        // Skrip dimuat dengan versi mtime agar perbaikan tidak tertahan cache.
        $this->assertStringContainsString("asset('js/berkah-checkout.js')", $view);
        $this->assertStringContainsString('filemtime(public_path(\'js/berkah-checkout.js\'))', $view);

        // Gerak dimatikan bila pengguna memintanya.
        $this->assertStringContainsString('(prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString("matchMedia('(prefers-reduced-motion: reduce)')", $script);
    }

    public function test_teks_panjang_diganti_kalimat_pendek_yang_punya_tujuan(): void
    {
        $view = $this->checkoutView();

        foreach ([
            'Isi kebutuhan untuk setiap unit',
            'Harga sewa dihitung berdasarkan tanggal atau paket yang dipilih',
            'Tanggal acara sekaligus menjadi tanggal papan diterima',
            'Foto merupakan contoh; desain akhir mengikuti permintaan pelanggan',
        ] as $panjang) {
            $this->assertStringNotContainsString($panjang, $view, 'Teks panjang masih tersisa: '.$panjang);
        }

        // Kalimat pengganti: satu janji, satu tindakan.
        $this->assertStringContainsString('Isi empat hal untuk tiap papan', $view);
        $this->assertStringContainsString('tanpa perlu chat', $view);
        $this->assertStringContainsString('Tanggal acara juga tanggal papan diterima', $view);

        $hook = (string) file_get_contents(resource_path('views/catalog/index.blade.php'));
        $this->assertStringContainsString('Dirangkai ulang untukmu.', $hook);
        $this->assertStringNotContainsString('Foto merupakan contoh', $hook);
    }

    public function test_kolom_total_harga_yang_beranimasi_dicabut_dari_katalog(): void
    {
        $index = (string) file_get_contents(resource_path('views/catalog/index.blade.php'));
        $show = (string) file_get_contents(resource_path('views/catalog/show.blade.php'));
        $script = (string) file_get_contents(public_path('js/berkah-variants.js'));

        foreach ([$index, $show] as $view) {
            $this->assertStringNotContainsString('data-bk-line-total', $view);
            $this->assertStringContainsString('class="price"', $view, 'Harga satuan tetap harus tampil.');
        }
        $this->assertStringNotContainsString('data-bk-line-total', $script);
        $this->assertStringNotContainsString('updateTotal', $script);
    }

    /**
     * Unggah foto desain sempat terlipat di dalam <details> sehingga pelanggan
     * tidak menemukan tempatnya. Kartu ini harus tetap terbuka di halaman.
     */
    public function test_kartu_unggah_referensi_tampil_terbuka_dan_menyebut_batas_ukuran(): void
    {
        $view = $this->checkoutView();

        $this->assertStringNotContainsString('<details class="bk-co-card bk-co-more" id="checkout-references"', $view);
        $this->assertStringContainsString('<section class="bk-co-card bk-co-refs" id="checkout-references"', $view);

        foreach ([
            'type="file" name="reference_images[]"',
            'accept="image/jpeg,image/png,image/webp"',
            'Maksimal 5 foto',
            '25 MB per foto',
            'total 60 MB',
            'href="#checkout-references"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $view, 'Kartu unggah referensi kehilangan: '.$needle);
        }

        // Batas 25 MB per foto = aturan validasi `max:25600` (KB) di controller.
        $controller = (string) file_get_contents(app_path('Http/Controllers/RentalOrderController.php'));
        $this->assertStringContainsString("'max:25600'", $controller);
        $this->assertStringContainsString('60 * 1024 * 1024', $controller);
    }

    /**
     * Setiap `for="…"` harus benar-benar menunjuk `<input|select|textarea>`
     * dengan id itu. Penjaga ini lahir dari kesalahan nyata: saat memangkas
     * teks "(opsional)", tag `<input` ikut terhapus dan label menunjuk `<span>`
     * — halaman tetap tampak wajar, tetapi berkas tidak bisa dipilih.
     */
    public function test_setiap_label_menunjuk_kendali_yang_ada(): void
    {
        $view = $this->checkoutView();
        preg_match_all('/for="([^"]+)"/', $view, $cocok);
        $this->assertNotEmpty($cocok[1], 'Halaman checkout harus punya label ber-for.');

        $salah = [];
        foreach (array_unique($cocok[1]) as $id) {
            $pola = '/<(input|select|textarea)\b[^>]*\bid="'.preg_quote($id, '/').'"/';
            if (preg_match($pola, $view) !== 1) {
                $salah[] = $id;
            }
        }

        $this->assertSame([], $salah, 'Label tanpa kendali: '.implode(', ', $salah));
    }

    public function test_pengesahan_ketentuan_tetap_ada_dan_bertanda_wajib(): void
    {
        $view = $this->checkoutView();

        $this->assertStringContainsString('name="terms"', $view);
        $this->assertStringContainsString('required', $view);
        $this->assertStringContainsString('menyetujui ketentuan penyewaan', $view);
        $this->assertStringContainsString('Ketentuan penyewaan', $view);

        // Lima poin hukum tetap bisa dibaca, hanya dilipat agar tidak mendominasi.
        $this->assertStringContainsString('<details class="bk-co-terms__more">', $view);
        $this->assertStringContainsString('Konfirmasi dan stok.', $view);
        $this->assertStringContainsString('DP dan pelunasan.', $view);
        $this->assertStringContainsString('Jangan kirim pembayaran nyata.', $view);
    }
}
