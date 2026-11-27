{{--
|--------------------------------------------------------------------------
| Ringkasan tahap satu baris (putaran 42)
|--------------------------------------------------------------------------
| Stepper lima langkah lengkap milik halaman Pesanan (detail pesanan dan
| halaman unit). Di beranda pelanggan, lima lingkaran berlabel untuk setiap
| unit terlalu berat: kartu menjadi sangat tinggi dan pada layar 390 px
| langkahnya pecah menjadi dua baris yang tampak seperti garis terputus.

| Di beranda cukup satu baris: "Tahap 3 dari 5 · Siap diserahkan" dengan lima
| segmen. Tahap lengkap tetap satu ketukan jauhnya lewat "Buka unit".
--}}
@php
    $stageTotal = count(\App\Support\RentalLabels::PROGRESS_STEPS);
    $stageCancelled = $unit->status === 'cancelled';
    // `progress` punya nilai bawaan di basis data, tetapi data impor lama bisa
    // kosong; label hampa akan menghasilkan "Tahap 1 dari 5 · ".
    $stageProgress = $unit->progress ?: 'waiting';
    $stageStep = \App\Support\RentalLabels::progressStep($stageProgress);
    $stageIndex = $stageStep ?? 0;
    $stageLabel = $stageCancelled ? 'Dibatalkan' : \App\Support\RentalLabels::progress($stageProgress);
    $stageNumber = $stageCancelled ? $stageIndex : $stageIndex + 1;
@endphp
<p class="bk-stage">
    <span class="bk-stage__track" aria-hidden="true">
        @for($i = 0; $i < $stageTotal; $i++)
            @php
                // Unit batal tidak pernah "selesai": hanya langkah yang sudah
                // lewat yang ditandai, tanpa satu pun segmen aktif.
                $segState = $i < $stageIndex ? 'done' : (! $stageCancelled && $i === $stageIndex ? 'current' : 'todo');
            @endphp
            <span class="bk-stage__seg" data-state="{{ $segState }}"></span>
        @endfor
    </span>
    <span class="bk-stage__text">
        @if($stageCancelled)
            <strong>Dibatalkan</strong> · sebelumnya {{ \App\Support\RentalLabels::progress($stageProgress) }}
        @else
            <strong>Tahap {{ $stageNumber }} dari {{ $stageTotal }}</strong> · {{ $stageLabel }}
        @endif
    </span>
</p>
