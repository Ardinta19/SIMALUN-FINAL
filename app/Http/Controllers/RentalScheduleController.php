<?php

namespace App\Http\Controllers;

use App\Models\RentalOrderUnit;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class RentalScheduleController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($input['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $input['month'], 'Asia/Jakarta')->startOfMonth()
            : CarbonImmutable::now('Asia/Jakarta')->startOfMonth();
        $end = $month->endOfMonth();
        $units = RentalOrderUnit::with('order.customer')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('starts_on', '<=', $end->toDateString())
            ->whereDate('ends_on', '>=', $month->toDateString())
            ->orderBy('starts_on')->orderBy('id')->get();
        $days = [];
        for ($date = $month; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $days[] = ['date' => $date, 'units' => $units->filter(fn ($unit) => $unit->starts_on->toDateString() <= $key && $unit->ends_on->toDateString() >= $key)];
        }
        return view('rental.schedule', compact('month', 'days'));
    }
}
