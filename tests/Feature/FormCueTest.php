<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Peringatan "Bagian ini wajib diisi." tidak boleh muncul sebagai balon
 * bawaan peramban. Penggantinya: satu kalimat kecil di bawah kolom yang
 * bermasalah, masuk dengan animasi tenang (§9.138).
 */
class FormCueTest extends TestCase
{
    private function js(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/public/js/berkah-controls.js');
    }

    private function css(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/public/css/berkah-controls.css');
    }

    public function test_formulir_memakai_validasi_kita_sendiri(): void
    {
        $js = $this->js();

        $this->assertStringContainsString('form.noValidate = true', $js,
            'Balon bawaan peramban harus dimatikan saat JavaScript aktif.');
        $this->assertStringContainsString('function validateConstraints(', $js);
        $this->assertStringContainsString('function showCue(', $js);
        $this->assertStringContainsString('function clearCue(', $js);
        $this->assertStringNotContainsString('window.alert(', $js, 'Tidak boleh ada popup alert.');
        $this->assertStringNotContainsString('.reportValidity()', $js, 'reportValidity memunculkan balon bawaan.');
    }

    /**
     * Validasi bawaan berjalan SEBELUM event submit, jadi mematikannya harus
     * dilakukan saat halaman dimuat — bukan di dalam handler submit.
     */
    public function test_balon_native_dimatikan_saat_halaman_dimuat(): void
    {
        $js = $this->js();

        $this->assertStringContainsString('function prepareForm(form)', $js);
        $this->assertStringContainsString("each(document, 'form', prepareForm)", $js);
        $this->assertStringContainsString("{ childList: true, subtree: true }", $js,
            'Formulir yang disisipkan belakangan juga harus disiapkan.');

        // Formulir yang penulisnya sudah memakai novalidate tidak diawasi.
        $this->assertStringContainsString("form.getAttribute('data-bk-cue-off') === '1'", $js);
        $this->assertStringContainsString("form.hasAttribute('novalidate')", $js);

        // Tombol "Lewati" dengan formnovalidate tetap boleh mengirim.
        $this->assertStringContainsString('event.submitter', $js);
        $this->assertStringContainsString('formNoValidate', $js);
    }

    public function test_petunjuk_memakai_teks_indonesia_yang_tenang(): void
    {
        $js = $this->js();

        $this->assertStringContainsString("' belum diisi.'", $js);
        $this->assertStringContainsString("'Bagian ini wajib diisi.'", $js);

        // Nama kolom dipakai lebih dulu, bukan kalimat generik.
        $this->assertStringContainsString('cueMessage(field)', $js);
        $this->assertStringContainsString('labelText(field)', $js);
    }

    public function test_petunjuk_terhubung_ke_kolom_untuk_pembaca_layar(): void
    {
        $js = $this->js();

        $this->assertStringContainsString("cue.setAttribute('role', 'alert')", $js);
        $this->assertStringContainsString("anchor.setAttribute('aria-describedby', described.join(' '))", $js);
        $this->assertStringContainsString("anchor.setAttribute('aria-invalid', 'true')", $js);
        $this->assertStringContainsString("anchor.removeAttribute('aria-invalid')", $js);
        $this->assertStringContainsString("anchor.removeAttribute('aria-describedby')", $js);

        // Atribut hanya dilepas bila memang kita yang memasangnya.
        $this->assertStringContainsString("anchor.setAttribute('data-bk-cue', '1')", $js);
        $this->assertStringContainsString("anchor.getAttribute('data-bk-cue') === '1'", $js);
    }

    public function test_kolom_tersembunyi_diwakili_trigger_penggantinya(): void
    {
        $js = $this->js();

        // Select yang sudah menjadi sheet tidak terlihat; petunjuk harus
        // menempel pada trigger-nya agar formulir tidak lolos ke server.
        $this->assertStringContainsString('function cueAnchor(field)', $js);
        $this->assertStringContainsString('field.__fcTrigger', $js);
        $this->assertStringContainsString('if (!cueAnchor(field)) continue;', $js);
    }

    public function test_gulir_ke_kolom_bermasalah_memakai_lenis_bila_ada(): void
    {
        $js = $this->js();

        $this->assertStringContainsString('window.BkMotion && window.BkMotion.lenis', $js);
        $this->assertStringContainsString('lenis.scrollTo(target', $js);

        // Fokus tetap dipindahkan tanpa melompat sendiri (preventScroll).
        $this->assertStringContainsString('focusQuiet(target)', $js);

        // Trigger pembungkus: fokus pindah ke elemen yang bisa difokuskan.
        $this->assertStringContainsString('focusableTarget(anchor) || anchor', $js);
        $this->assertStringContainsString('focus({ preventScroll: true })', $js,
            'Fokus tidak boleh memicu lompatan gulir kedua.');
    }

    public function test_gaya_petunjuk_mikro_dan_menghormati_gerak_dikurangi(): void
    {
        $css = $this->css();

        $this->assertStringContainsString('.bk-cue {', $css);
        $this->assertStringContainsString('.bk-cue--in {', $css);
        $this->assertStringContainsString('transition: opacity .18s', $css);
        $this->assertStringContainsString('@keyframes bk-cue-pulse', $css);
        $this->assertMatchesRegularExpression(
            '/prefers-reduced-motion:\s*reduce[\s\S]{0,200}\.bk-cue \{[^}]*transition: none/',
            $css,
            'Pengguna yang meminta gerak dikurangi harus melihat petunjuk tanpa animasi.'
        );
    }
}
