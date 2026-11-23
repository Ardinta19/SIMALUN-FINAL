<?php

namespace App\Services;

use App\Models\RentalOrderUnit;
use App\Models\RentalPayment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Pengingat untuk dua pihak yang saling menunggu.
 *
 * Setiap pengingat hanya dikirim sekali per unit, per jenis, per penerima,
 * per hari. Kuncinya ada di tabel `rental_unit_reminders` (unik), sehingga
 * perintah ini aman dijalankan berkali-kali dan boleh dijadwalkan rapat.
 *
 * Pengingat tidak mengubah status apa pun: ia hanya memberi tahu pihak yang
 * seharusnya bergerak, tanpa mengambil alih keputusan bisnis.
 */
class RentalReminders
{
    /** Bukti pembayaran dianggap menganggur setelah sekian jam. */
    public const PROOF_WAITING_HOURS = 6;

    /** Pelanggan diberi waktu sekian jam sebelum diingatkan soal desain. */
    public const DESIGN_WAIT_HOURS = 24;

    /** Admin dieskalasi bila respons desain melewati sekian jam. */
    public const DESIGN_ESCALATION_HOURS = 48;

    /** Eskalasi juga dipicu bila acara tinggal sekian hari lagi. */
    public const DESIGN_ESCALATION_DAYS = 3;

    /**
     * @return array{proof:int, due:int, design:int, design_escalation:int}
     */
    public function run(): array
    {
        $counts = ['proof' => 0, 'due' => 0, 'design' => 0, 'design_escalation' => 0];
        $adminsReachable = User::where('role', 'admin')->where('is_active', true)->exists();

        if ($adminsReachable) $this->remindProofs($counts);
        $this->remindPaymentDue($counts);
        $this->remindDesign($counts, $adminsReachable);

        return $counts;
    }

    /** Bukti pembayaran yang menunggu verifikasi terlalu lama. */
    private function remindProofs(array &$counts): void
    {
        $threshold = now()->subHours(self::PROOF_WAITING_HOURS);
        RentalPayment::query()->with('unit.order')
            ->where('status', 'pending')
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '<=', $threshold)
            ->chunkById(200, function ($payments) use (&$counts) {
                foreach ($payments as $payment) {
                    $unit = $payment->unit;
                    if (! $unit || ! $unit->order) continue;
                    if (! $this->claim($unit, 'payment_proof_pending', 'admins')) continue;
                    app(RentalNotifications::class)->admins(
                        $unit->order,
                        'Bukti menunggu verifikasi',
                        'Bukti Rp '.number_format((int) $payment->amount, 0, ',', '.').' untuk unit '.$unit->product_name
                            .' sudah menunggu lebih dari '.self::PROOF_WAITING_HOURS.' jam. Verifikasi agar serah terima tidak tertahan.'
                    );
                    $counts['proof']++;
                }
            });
    }

    /** Pelunasan H-1 untuk unit yang belum tertutup bukti tepat waktu. */
    private function remindPaymentDue(array &$counts): void
    {
        $tomorrow = CarbonImmutable::now('Asia/Jakarta')->addDay()->toDateString();
        $policy = app(RentalPaymentDeadline::class);

        RentalOrderUnit::query()->with('order')
            ->where('status', RentalOrderUnit::STATUS_CONFIRMED)
            ->whereDate('starts_on', $tomorrow)
            ->whereNotIn('progress', ['handed_over', 'returned'])
            ->chunkById(200, function ($units) use (&$counts, $policy) {
                foreach ($units as $unit) {
                    if (! $this->customerReachable($unit)) continue;
                    $payable = $unit->payableAmount();
                    // Ongkos belum disetujui: yang ditunggu admin, bukan pelanggan.
                    if ($payable === null) continue;
                    $covered = $policy->timelyCoverage($unit);
                    if ($covered >= $payable) continue;
                    if (! $this->claim($unit, 'payment_due', 'customer')) continue;
                    app(RentalNotifications::class)->customer(
                        $unit->order,
                        'Pelunasan jatuh tempo malam ini',
                        'Sisa Rp '.number_format(max(0, $payable - $covered), 0, ',', '.').' untuk unit '.$unit->product_name
                            .' harus dibayar sebelum pukul 23.59 WIB hari ini agar jadwal tidak dibatalkan otomatis.'
                    );
                    $counts['due']++;
                }
            });
    }

    /** Desain yang menunggu pelanggan; admin dieskalasi bila terlalu lama. */
    private function remindDesign(array &$counts, bool $adminsReachable): void
    {
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $customerThreshold = now()->subHours(self::DESIGN_WAIT_HOURS);
        $escalationThreshold = now()->subHours(self::DESIGN_ESCALATION_HOURS);
        $nearEvent = $today->addDays(self::DESIGN_ESCALATION_DAYS)->toDateString();

        RentalOrderUnit::query()->with(['order', 'previews'])
            ->where('status', RentalOrderUnit::STATUS_CONFIRMED)
            ->where('progress', 'waiting')
            ->whereDate('starts_on', '>=', $today->toDateString())
            ->whereHas('previews', fn ($query) => $query->where('status', 'pending')->where('created_at', '<=', $customerThreshold))
            ->chunkById(200, function ($units) use (&$counts, $adminsReachable, $customerThreshold, $escalationThreshold, $nearEvent) {
                foreach ($units as $unit) {
                    $latest = $unit->previews->first();
                    // Versi terbaru harus benar-benar yang menganggur: preview lama
                    // yang tertinggal tidak boleh memicu pengingat untuk versi baru.
                    if (! $latest || $latest->status !== 'pending' || $latest->created_at->greaterThan($customerThreshold)) continue;

                    if ($this->customerReachable($unit) && $this->claim($unit, 'design_response', 'customer')) {
                        app(RentalNotifications::class)->customer(
                            $unit->order,
                            'Preview desain menunggu',
                            'Preview desain unit '.$unit->product_name.' masih menunggu persetujuanmu. '
                                .'Pengerjaan belum bisa dimulai sebelum desain disetujui.'
                        );
                        $counts['design']++;
                    }

                    $overdue = $latest->created_at->lessThanOrEqualTo($escalationThreshold)
                        || $unit->starts_on->toDateString() <= $nearEvent;
                    if ($adminsReachable && $overdue && $this->claim($unit, 'design_escalation', 'admins')) {
                        app(RentalNotifications::class)->admins(
                            $unit->order,
                            'Pelanggan belum merespons desain',
                            'Preview unit '.$unit->product_name.' belum direspons pelanggan. Hubungi pelanggan atau putuskan langkah berikutnya.'
                        );
                        $counts['design_escalation']++;
                    }
                }
            });
    }

    private function customerReachable(RentalOrderUnit $unit): bool
    {
        $order = $unit->order;

        return $order && $order->customer && $order->customer->is_active;
    }

    /**
     * Catat pengingat hari ini. Hanya yang berhasil mencatat baris baru yang
     * boleh mengirim notifikasi, sehingga dua proses penjadwal tidak menggandakan
     * pesan dan pengingat yang sama tidak terulang di hari yang sama.
     */
    private function claim(RentalOrderUnit $unit, string $kind, string $recipient): bool
    {
        return DB::table('rental_unit_reminders')->insertOrIgnore([
            'rental_order_unit_id' => $unit->id,
            'kind' => $kind,
            'recipient' => $recipient,
            'sent_on' => CarbonImmutable::now('Asia/Jakarta')->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }
}
