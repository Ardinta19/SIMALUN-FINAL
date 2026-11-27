<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Kolom DATE yang benar-benar disimpan sebagai tanggal (`Y-m-d`).
 *
 * Cast bawaan `date` menyimpan nilainya lewat format tanggal model
 * (`Y-m-d H:i:s`), jadi di SQLite — basis data yang dipakai tes — kolom
 * `date` berisi `2026-11-30 00:00:00`. Perbandingan rentang dengan
 * `where('event_on', '<=', '2026-11-30')` lalu GAGAL untuk hari terakhir,
 * sementara di MySQL (kolom DATE) query yang sama berhasil. Perbedaan itu
 * membuat tes lolos/gagal tergantung basis data, dan sempat menutupi
 * kesalahan pada audit sebelumnya (§9.127).
 *
 * Dengan cast ini nilai disimpan apa adanya sebagai `Y-m-d`, sehingga
 * `where()`, `whereIn()`, dan `=` pada kolom tanggal berperilaku sama di
 * MySQL maupun SQLite dan tetap memakai indeks (tanpa `DATE()`/`whereDate`).
 * Zona waktu mengikuti `app.timezone`, sama seperti cast `date` bawaan.
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $value)->toDateString();
    }
}
