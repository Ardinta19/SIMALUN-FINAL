<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use Carbon\CarbonImmutable;

/**
 * Tenggat respons desain per unit.
 *
 * Pelanggan diberi waktu untuk menyetujui atau meminta revisi. Setelah tenggat
 * lewat, admin boleh memulai pengerjaan dengan catatan tertulis tanpa persetujuan
 * pelanggan, supaya operasional tidak terkunci. Tenggat dihitung dari preview
 * terbaru yang masih menunggu respons:
 *
 * - 48 jam setelah preview diunggah, atau
 * - 3 hari sebelum tanggal penerimaan, bila itu lebih cepat.
 *
 * Untuk pesanan mendadak (preview diunggah kurang dari 3 hari sebelum acara),
 * tenggat tidak pernah jatuh sebelum preview diunggah, sehingga admin dapat
 * memutuskan segera setelah preview tersedia.
 */
class RentalDesignDeadline
{
    /** Pelanggan diingatkan setelah preview menganggur sekian jam. */
    public const WAIT_HOURS = 24;

    /** Batas respons normal setelah preview diunggah. */
    public const DEADLINE_HOURS = 48;

    /** Batas respons juga dimajukan bila acara tinggal sekian hari lagi. */
    public const DEADLINE_DAYS = 3;

    public function deadline(RentalOrderUnit $unit): ?CarbonImmutable
    {
        $latest = $unit->previews()->first();
        if (! $latest || $latest->status !== 'pending') return null;

        $uploaded = CarbonImmutable::instance($latest->created_at);
        $beforeEvent = CarbonImmutable::parse($unit->starts_on->toDateString(), 'Asia/Jakarta')->subDays(self::DEADLINE_DAYS);
        $deadline = $uploaded->addHours(self::DEADLINE_HOURS)->min($beforeEvent);

        return $deadline->lessThan($uploaded) ? $uploaded : $deadline;
    }

    public function reached(RentalOrderUnit $unit): bool
    {
        $deadline = $this->deadline($unit);

        return $deadline !== null && now('Asia/Jakarta')->greaterThanOrEqualTo($deadline);
    }
}
