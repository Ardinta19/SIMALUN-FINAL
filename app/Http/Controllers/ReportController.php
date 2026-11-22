<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\RentalPaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    /**
     * Form "Lapor Kendala" — shared untuk semua role.
     */
    public function create()
    {
        $myReports = Auth::user()->reports()
            ->latest()
            ->take(5)
            ->get();

        $settings = RentalPaymentSetting::find(1);

        return view('reports.create', compact('myReports', 'settings'));
    }

    /**
     * Simpan laporan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => ['required', 'in:bug,saran,komplain'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'screenshot' => ['nullable', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
        ], [
            'description.min' => 'Deskripsi minimal 10 karakter agar kami bisa memahami kendala kamu.',
            'screenshot.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('private-reports', 'local');
            if (! $screenshotPath) {
                throw ValidationException::withMessages(['screenshot' => 'Gambar tidak dapat disimpan. Silakan coba lagi.']);
            }
        }

        try {
            Report::create([
                'user_id' => Auth::id(),
                'category' => $request->category,
                'description' => $request->description,
                'screenshot_path' => $screenshotPath,
                'status' => 'open',
            ]);
        } catch (\Throwable $e) {
            if ($screenshotPath) Storage::disk('local')->delete($screenshotPath);
            throw $e;
        }

        return back()->with('success', 'Laporan berhasil dikirim. Terima kasih atas masukannya!');
    }

    /** Lampiran baru privat. Lampiran lama tetap tersedia dari lokasi lama untuk admin. */
    public function screenshot(Report $report)
    {
        $path = $report->screenshot_path;
        abort_unless(is_string($path), 404);
        if (preg_match('~^private-reports/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)$~i', $path)) {
            $disk = 'local';
        } elseif (preg_match('~^reports/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)$~i', $path)) {
            $disk = 'public';
        } else {
            abort(404);
        }
        abort_unless(Storage::disk($disk)->exists($path), 404);
        return Storage::disk($disk)->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    /**
     * Admin: daftar semua laporan masuk.
     */
    public function adminIndex(Request $request)
    {
        $status = $request->get('status');
        $category = $request->get('category');

        $query = Report::with('user')->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category', $category);
        }

        $reports = $query->paginate(15)->withQueryString();

        $countOpen = Report::where('status', 'open')->count();
        $countInProgress = Report::where('status', 'in_progress')->count();
        $countResolved = Report::whereIn('status', ['resolved', 'closed'])->count();

        return view('roles.admin.reports', compact(
            'reports', 'countOpen', 'countInProgress', 'countResolved', 'status', 'category'
        ));
    }

    /**
     * Admin: update status laporan.
     */
    public function updateStatus(Request $request, Report $report)
    {
        $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $data = ['status' => $request->status];

        if ($request->filled('admin_notes')) {
            $data['admin_notes'] = $request->admin_notes;
        }

        if (in_array($request->status, ['resolved', 'closed'])) {
            $data['resolved_at'] = $report->resolved_at ?? now();
        } else {
            $data['resolved_at'] = null;
        }

        $report->update($data);

        return back()->with('success', 'Status laporan berhasil diperbarui.');
    }
}
