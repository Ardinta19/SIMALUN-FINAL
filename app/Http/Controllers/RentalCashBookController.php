<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use App\Support\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RentalCashBookController extends Controller
{
    public function index(Request $request)
    {
        [$period, $date, $start, $end, $previousStart, $previousEnd] = $this->periodBounds($request);
        $summary = fn ($from, $to) => $this->entriesBetween($from, $to)->selectRaw('entry_type, SUM(amount) as total')->groupBy('entry_type')->pluck('total', 'entry_type');
        $current = $summary($start, $end); $previous = $summary($previousStart, $previousEnd);
        $entries = $this->entriesBetween($start, $end)->orderByDesc('entry_date')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('rental.cashbook', compact('period', 'date', 'start', 'end', 'previousStart', 'previousEnd', 'current', 'previous', 'entries'));
    }

    public function export(Request $request)
    {
        [, , $start, $end] = $this->periodBounds($request);
        $filename = 'buku-kas-'.$start->toDateString().'-'.$end->toDateString().'.csv';
        return response()->streamDownload(function () use ($start, $end) {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal', 'Jenis', 'Kategori', 'Nominal', 'Sumber', 'Catatan'], ',', '"', '');
            foreach ($this->entriesBetween($start, $end)->orderBy('entry_date')->orderBy('id')->cursor() as $entry) {
                fputcsv($out, [$entry->entry_date->toDateString(), $entry->entry_type === 'income' ? 'Pemasukan' : 'Pengeluaran', $this->csvText($entry->category), $entry->amount, $this->csvText($entry->source_type), $this->csvText($entry->notes)], ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function csvText(?string $value): string
    {
        $value = $value ?? '';
        return preg_match('/^[\s\x00-\x1f]*[=+@-]/u', $value) ? "'".$value : $value;
    }

    private function entriesBetween(CarbonImmutable $start, CarbonImmutable $end)
    {
        return FinanceEntry::query()->where('entry_date', '>=', $start->toDateString())->where('entry_date', '<=', $end->toDateString());
    }

    private function periodBounds(Request $request): array
    {
        $data = $request->validate(['period' => ['nullable', Rule::in(['day', 'week', 'month', 'year'])], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $period = $data['period'] ?? 'month';
        $date = isset($data['date']) ? CarbonImmutable::createFromFormat('!Y-m-d', $data['date'], 'Asia/Jakarta') : CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        [$start, $end, $previousStart, $previousEnd] = match ($period) {
            'day' => [$date, $date, $date->subDay(), $date->subDay()],
            'week' => [$date->startOfWeek(), $date->endOfWeek(), $date->subWeek()->startOfWeek(), $date->subWeek()->endOfWeek()],
            'year' => [$date->startOfYear(), $date->endOfYear(), $date->subYear()->startOfYear(), $date->subYear()->endOfYear()],
            default => [$date->startOfMonth(), $date->endOfMonth(), $date->subMonthNoOverflow()->startOfMonth(), $date->subMonthNoOverflow()->endOfMonth()],
        };
        return [$period, $date, $start, $end, $previousStart, $previousEnd];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data) {
            $entry = FinanceEntry::create($data + ['period_key' => substr($data['entry_date'], 0, 7), 'source_type' => 'manual', 'created_by' => $request->user()->id]);
            Audit::log('cashbook.manual.create', $entry, after: $entry->only(['entry_date', 'entry_type', 'category', 'amount', 'notes']));
        });
        return back()->with('success', 'Catatan kas berhasil ditambahkan.');
    }

    public function update(Request $request, FinanceEntry $entry)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($entry, $data) {
            $entry = FinanceEntry::lockForUpdate()->findOrFail($entry->id);
            abort_unless($entry->source_type === 'manual' && $entry->source_id === null, 403);
            $before = $entry->only(['entry_date', 'entry_type', 'category', 'amount', 'notes']);
            $entry->update($data + ['period_key' => substr($data['entry_date'], 0, 7)]);
            Audit::log('cashbook.manual.update', $entry, before: $before, after: $entry->only(['entry_date', 'entry_type', 'category', 'amount', 'notes']));
        });
        return back()->with('success', 'Catatan kas manual berhasil diperbarui.');
    }

    /**
     * Hapus catatan kas manual. Catatan yang lahir dari pembayaran sewa tidak
     * boleh dihapus dari sini: sumbernya pesanan dan pembayarannya, bukan buku
     * kas — menghapusnya akan membuat laporan tidak lagi cocok dengan pesanan.
     */
    public function destroy(FinanceEntry $entry)
    {
        DB::transaction(function () use ($entry) {
            $locked = FinanceEntry::lockForUpdate()->findOrFail($entry->id);
            abort_unless($locked->source_type === 'manual' && $locked->source_id === null, 403, 'Catatan dari pembayaran sewa tidak bisa dihapus dari buku kas.');
            $before = $locked->only(['entry_date', 'entry_type', 'category', 'amount', 'notes']);
            $locked->delete();
            Audit::log('cashbook.manual.delete', null, before: $before, summary: 'Catatan kas manual dihapus: '.$before['notes']);
        });

        return back()->with('success', 'Catatan kas manual dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(['entry_date' => ['required', 'date_format:Y-m-d'], 'entry_type' => ['required', Rule::in(['income', 'expense'])], 'category' => ['required', 'string', 'max:50'], 'amount' => ['required', 'integer', 'min:1', 'max:1000000000'], 'notes' => ['required', 'string', 'max:1000']]);
        if ($data['entry_type'] === 'income') $request->validate(['not_rental_payment' => ['accepted']]);
        return $data;
    }
}
