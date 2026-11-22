<?php

namespace App\Http\Controllers;

use App\Models\RentalOrderUnit;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RentalScheduleController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed'])],
            'activity' => ['nullable', Rule::in(['receiving', 'event', 'returning'])],
        ]);
        $month = isset($input['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $input['month'], 'Asia/Jakarta')->startOfMonth()
            : CarbonImmutable::now('Asia/Jakarta')->startOfMonth();
        $end = $month->endOfMonth();
        $units = RentalOrderUnit::with('order.customer')
            ->whereIn('status', ['pending', 'confirmed'])
            ->when(! empty($input['status']), fn ($query) => $query->where('status', $input['status']))
            ->whereDate('starts_on', '<=', $end->toDateString())
            ->whereDate('ends_on', '>=', $month->toDateString())
            ->orderBy('starts_on')->orderBy('id')->get();
        $days = [];
        for ($date = $month; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $days[] = ['date' => $date, 'units' => $units->filter(function ($unit) use ($key, $input) {
                return match ($input['activity'] ?? null) {
                    'receiving' => $unit->starts_on->toDateString() === $key,
                    'event' => $unit->event_on->toDateString() === $key,
                    'returning' => $unit->ends_on->toDateString() === $key,
                    default => $unit->starts_on->toDateString() <= $key && $unit->ends_on->toDateString() >= $key,
                };
            })];
        }
        return view('rental.schedule', compact('month', 'days', 'input'));
    }
}
