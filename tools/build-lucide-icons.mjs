/**
 * Bangkitkan peta glyph ikon dari paket Lucide resmi.
 * ---------------------------------------------------------------------------
 * Sumber: public/vendor/lucide/icons/*.svg  (disalin dari `lucide-static`,
 * lisensi ISC, lihat public/vendor/lucide/LICENSE).
 * Keluaran: resources/views/layouts/component/_icon_glyphs.php
 *
 * Kenapa dibuat begini:
 *   - Geometri ikon tidak lagi disalin tangan. Kalau ada versi Lucide baru,
 *     cukup `npm pack lucide-static`, salin SVG yang dipakai, lalu jalankan
 *     skrip ini. Tidak ada jalur CDN dan tidak ada berkas yang diunduh saat
 *     halaman dibuka.
 *   - Partial `_icon.blade.php` tetap memegang logika (alias, normalisasi
 *     ukuran, fallback), jadi kontrak aksesibilitasnya tidak berubah.
 *
 * Pakai:
 *   node tools/build-lucide-icons.mjs
 */

import { readdir, readFile, writeFile } from 'node:fs/promises';
import { extname, join } from 'node:path';

const ICON_DIR = 'public/vendor/lucide/icons';
const TARGET = 'resources/views/layouts/component/_icon_glyphs.php';

/** Ambil isi <svg> tanpa pembungkus, komentar, dan spasi berlebih. */
function innerMarkup(svg) {
    const body = svg
        .replace(/<!--[\s\S]*?-->/g, '')
        .replace(/^[\s\S]*?<svg[^>]*>/i, '')
        .replace(/<\/svg>[\s\S]*$/i, '');

    return body
        .replace(/\s+/g, ' ')
        .replace(/\s*\/>/g, '/>')
        .trim();
}

const files = (await readdir(ICON_DIR)).filter((file) => extname(file) === '.svg').sort();

const glyphs = {};
for (const file of files) {
    const name = file.replace(/\.svg$/, '');
    const markup = innerMarkup(await readFile(join(ICON_DIR, file), 'utf8'));
    if (!markup) {
        throw new Error(`SVG kosong: ${file}`);
    }
    glyphs[name] = markup;
}

const php = [
    '<?php',
    '',
    '/*',
    ' |--------------------------------------------------------------------------',
    ' | Peta glyph ikon — DIBANGKITKAN OTOMATIS, JANGAN DISUNTING TANGAN.',
    ' |--------------------------------------------------------------------------',
    ' | Sumber  : public/vendor/lucide/icons/*.svg (lucide-static, lisensi ISC)',
    ' | Perakit : node tools/build-lucide-icons.mjs',
    ' | Pemakai : resources/views/layouts/component/_icon.blade.php',
    ' |',
    ` | Glyph   : ${Object.keys(glyphs).length} ikon, viewBox 0 0 24 24.`,
    ' | Stroke, ukuran, dan atribut aksesibilitas dipasang oleh partial, bukan',
    ' | di sini — supaya seluruh ikon selalu tampil dengan ketebalan yang sama.',
    ' |--------------------------------------------------------------------------',
    ' */',
    '',
    'return [',
];

for (const [name, markup] of Object.entries(glyphs)) {
    php.push(`    '${name}' => '${markup.replace(/'/g, "\\'")}',`);
}

php.push('];', '');

await writeFile(TARGET, php.join('\n'), 'utf8');

console.log(`Glyph ditulis: ${Object.keys(glyphs).length} ikon → ${TARGET}`);
