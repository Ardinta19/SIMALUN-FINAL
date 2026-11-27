<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Penjaga kolom untuk pemanggilan agregat (`min`, `max`, `sum`, `avg`,
 * `increment`, `decrement`, `pluck`).
 *
 * Kenapa perlu: `RentalOrderUnit::min('confirmation_deadline')` pernah dipakai
 * padahal kolom itu menempel pada `rental_orders`, bukan `rental_order_units`.
 * SQLite memperlakukan pengenal berkutip ganda yang tidak dikenal sebagai
 * STRING, jadi kueri tidak melempar galat — ia mengembalikan teks
 * "confirmation_deadline", lalu `Carbon::parse()` meledak dan dasbor admin
 * menjadi 500 begitu ada unit berstatus menunggu. Kesalahan seperti ini tidak
 * terlihat sampai ada data, jadi diperiksa langsung dari sumbernya.
 */
class QueryColumnTest extends TestCase
{
    /** Kolom yang dibuat pembantu Blueprint, bukan lewat nama eksplisit. */
    private const IMPLICIT_COLUMNS = [
        'timestamps' => ['created_at', 'updated_at'],
        'timestampsTz' => ['created_at', 'updated_at'],
        'softDeletes' => ['deleted_at'],
        'softDeletesTz' => ['deleted_at'],
        'rememberToken' => ['remember_token'],
    ];

    /** @return array<string, array<string, true>> tabel => kolom */
    private function columnsByTable(): array
    {
        $tables = [];
        $blocks = '~Schema::(?:create|table)\(\s*\'([^\']+)\'\s*,\s*function\s*\(Blueprint\s*\$table\)\s*\{(.+?)\n\s*\}\);~s';

        foreach (File::files(database_path('migrations')) as $file) {
            $source = (string) file_get_contents($file->getPathname());
            preg_match_all($blocks, $source, $found, PREG_SET_ORDER);

            foreach ($found as $block) {
                $table = $block[1];
                $body = $block[2];
                $tables[$table] ??= [];

                if (preg_match('~\$table->(id|increments|bigIncrements)\(~', $body)) {
                    $tables[$table]['id'] = true;
                }

                preg_match_all('~\$table->[a-zA-Z]+\(\s*\'([^\']+)\'~', $body, $named);
                foreach ($named[1] as $column) {
                    $tables[$table][$column] = true;
                }

                preg_match_all('~\$table->([a-zA-Z]+)\(~', $body, $helpers);
                foreach ($helpers[1] as $helper) {
                    foreach (self::IMPLICIT_COLUMNS[$helper] ?? [] as $column) {
                        $tables[$table][$column] = true;
                    }
                }
            }
        }

        return $tables;
    }

    /** @return array<string, string> nama kelas pendek & FQCN => tabel */
    private function tablesByModel(): array
    {
        $map = [];

        foreach (File::files(app_path('Models')) as $file) {
            $name = $file->getFilenameWithoutExtension();
            $source = (string) file_get_contents($file->getPathname());
            $table = preg_match('~protected \$table\s*=\s*\'([^\']+)\'~', $source, $match)
                ? $match[1]
                : strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . 's';

            $map['App\\Models\\' . $name] = $table;
            $map[$name] = $table;
        }

        return $map;
    }

    public function test_agregat_hanya_memakai_kolom_yang_benar_benar_ada_di_tabel_model(): void
    {
        $tables = $this->columnsByTable();
        $models = $this->tablesByModel();

        $pattern = '~([A-Z][A-Za-z0-9_]*)::[^;{}]{0,160}?->(min|max|sum|avg|increment|decrement|pluck)\(\s*\'([a-z_][a-z0-9_]*)\'~';
        $offenders = [];

        foreach (File::allFiles(app_path()) as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            preg_match_all($pattern, (string) file_get_contents($file->getPathname()), $calls, PREG_SET_ORDER);

            foreach ($calls as $call) {
                [, $model, $method, $column] = $call;
                $table = $models[$model] ?? null;

                if ($table === null || ! isset($tables[$table]) || isset($tables[$table][$column])) {
                    continue;
                }

                $offenders[] = $model . ' -> ' . $method . '(' . $column . ') — tidak ada di tabel ' . $table;
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($offenders)),
            'Ada agregat yang membaca kolom di tabel yang salah.'
        );
    }

    public function test_tenggat_konfirmasi_terdekat_dibaca_dari_pesanan(): void
    {
        $controller = (string) file_get_contents(app_path('Http/Controllers/DashboardController.php'));

        $this->assertStringNotContainsString(
            "RentalOrderUnit::where('status', 'pending')->min('confirmation_deadline')",
            $controller,
            'Tenggat konfirmasi ada di rental_orders, bukan rental_order_units.'
        );
        $this->assertStringContainsString('min(\'confirmation_deadline\')', $controller);
    }
}
