<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Setiap kolom yang divalidasi punya nama Indonesia.
 *
 * Bila sebuah kolom tidak terdaftar di `lang/id/validation.php` bagian
 * `attributes`, Laravel memakai nama kolom mentah di dalam pesan galat:
 * "ends on harus berformat Y-m-d", "The base field must be an integer".
 * Pelanggan melihat nama variabel, bukan nama isian.
 *
 * Pemeriksaan ini membaca blok validasi di controller (`->validate([...])`
 * dan `Validator::make(...)`) lalu mencocokkan kuncinya dengan daftar
 * atribut. Kunci bertingkat seperti `units.*.recipient_name` harus terdaftar
 * persis.
 */
class ValidationAttributeTest extends TestCase
{
    /** @return array<int, string> */
    private function controllerFiles(): array
    {
        $out = [];
        foreach (['app/Http/Controllers', 'app/Console/Commands'] as $dir) {
            $root = base_path($dir);
            if (! is_dir($root)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                    $out[] = str_replace('\\', '/', $file->getPathname());
                }
            }
        }
        sort($out);

        return $out;
    }

    /** @return array<int, string> */
    private function attributes(): array
    {
        $source = (string) file_get_contents(base_path('lang/id/validation.php'));
        $start = strpos($source, "'attributes'");
        if ($start === false) {
            return [];
        }

        $end = strpos($source, "\n    ],", $start);
        $block = $end === false ? substr($source, $start) : substr($source, $start, $end - $start);

        preg_match_all("/^\s*'([^']+)'\s*=>/m", $block, $cocok);

        return $cocok[1];
    }

    /**
     * Ambil isi array aturan setelah `->validate([` (kurung kurawal siku
     * diimbangi, string dilewati) supaya kunci di dalamnya bisa dibaca.
     */
    private function ruleBlock(string $source, int $openBracket): ?string
    {
        $depth = 0;
        $length = strlen($source);
        $inString = null;

        for ($i = $openBracket; $i < $length; $i++) {
            $char = $source[$i];

            if ($inString !== null) {
                if ($char === '\\') {
                    $i++;
                    continue;
                }
                if ($char === $inString) {
                    $inString = null;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = $char;
                continue;
            }
            if ($char === '[') {
                $depth++;
                continue;
            }
            if ($char === ']') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $openBracket, $i - $openBracket + 1);
                }
            }
        }

        return null;
    }

    public function test_setiap_kolom_validasi_punya_nama_indonesia(): void
    {
        $attributes = $this->attributes();
        $this->assertTrue(count($attributes) > 60, 'Daftar atribut tidak terbaca dengan benar.');

        $masalah = [];
        $diperiksa = 0;

        foreach ($this->controllerFiles() as $path) {
            $source = (string) file_get_contents($path);
            $relatif = str_replace(base_path().'/', '', $path);

            foreach (['->validate([', 'Validator::make(', '->validate('] as $penanda) {
                $offset = 0;
                while (($pos = strpos($source, $penanda, $offset)) !== false) {
                    $offset = $pos + strlen($penanda);
                    $open = strpos($source, '[', $offset);
                    if ($open === false || $open - $offset > 400) {
                        continue;
                    }
                    $block = $this->ruleBlock($source, $open);
                    if ($block === null) {
                        continue;
                    }

                    preg_match_all("/'([a-zA-Z0-9_.*]+)'\s*=>\s*\[/", $block, $cocok);
                    foreach (array_unique($cocok[1]) as $field) {
                        $diperiksa++;
                        if (! in_array($field, $attributes, true)) {
                            $masalah[] = $relatif.' → '.$field;
                        }
                    }
                }
            }
        }

        $masalah = array_values(array_unique($masalah));
        $this->assertTrue($diperiksa > 40, 'Blok validasi tidak terbaca dengan benar.');
        $this->assertSame([], $masalah, implode(' | ', $masalah));
    }
}
