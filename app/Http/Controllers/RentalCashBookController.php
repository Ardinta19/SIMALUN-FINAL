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
        $data = $request->validate(['period' => ['nullable', Rule::in(['day', 'week', 'month', 'year'])], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $period = $data['period'] ?? 'month';
        $date = isset($data['date']) ? CarbonImmutable::createFromFormat('!Y-m-d', $data['date'], 'Asia/Jakarta') : CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        [$start, $end, $previousStart, $previousEnd] = match ($period) {
            'day' => [$date, $date, $date->subDay(), $date->subDay()],
            'week' => [$date->startOfWeek(), $date->endOfWeek(), $date->subWeek()->startOfWeek(), $date->subWeek()->endOfWeek()],
            'year' => [$date->startOfYear(), $date->endOfYear(), $date->subYear()->startOfYear(), $date->subYear()->endOfYear()],
            default => [$date->startOfMonth(), $date->endOfMonth(), $date->subMonthNoOverflow()->startOfMonth(), $date->subMonthNoOverflow()->endOfMonth()],
        };
        $summary = fn ($from, $to) => FinanceEntry::whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])->selectRaw('entry_type, SUM(amount) as total')->groupBy('entry_type')->pluck('total', 'entry_type');
        $current = $summary($start, $end); $previous = $summary($previousStart, $previousEnd);
        $entries = FinanceEntry::whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])->orderByDesc('entry_date')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('rental.cashbook', compact('period', 'date', 'start', 'end', 'previousStart', 'previousEnd', 'current', 'previous', 'entries'));
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
            abort_unless($entry->source_type === 'manual' && $entry->order_id === null && $entry->source_id === null, 403);
            $before = $entry->only(['entry_date', 'entry_type', 'category', 'amount', 'notes']);
            $entry->update($data + ['period_key' => substr($data['entry_date'], 0, 7)]);
            Audit::log('cashbook.manual.update', $entry, before: $before, after: $entry->only(['entry_date', 'entry_type', 'category', 'amount', 'notes']));
        });
        return back()->with('success', 'Catatan kas manual berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(['entry_date' => ['required', 'date_format:Y-m-d'], 'entry_type' => ['required', Rule::in(['income', 'expense'])], 'category' => ['required', 'string', 'max:50'], 'amount' => ['required', 'integer', 'min:1', 'max:1000000000'], 'notes' => ['required', 'string', 'max:1000']]);
        if ($data['entry_type'] === 'income') $request->validate(['not_rental_payment' => ['accepted']]);
        return $data;
    }
}
