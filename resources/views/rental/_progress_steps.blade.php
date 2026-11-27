{{--
|--------------------------------------------------------------------------
| Stepper progres unit (putaran 35)
|--------------------------------------------------------------------------
| Pelanggan sebelumnya hanya melihat "Menunggu pengerjaan/Dalam pengerjaan"
| sebagai satu teks, dan harus membuka halaman unit untuk tahu urutannya.
| Di sini lima langkah ditampilkan sekaligus: yang sudah lewat ditandai
| selesai, langkah sekarang diwarnai, sisanya abu-abu.

| Tanpa CSS pun tetap terbaca karena ini daftar berurut; tanpa JavaScript
| pun tidak ada yang perlu dijalankan — semuanya dirender dari data.
|
| Putaran 42: hanya dipakai di halaman pesanan (detail pesanan dan halaman
| unit). Beranda pelanggan memakai `_progress_bar` yang jauh lebih ringkas,
| jadi mode `compact` dihapus supaya tidak ada dua bentuk stepper.
--}}
@php
    $progressStep = \App\Support\RentalLabels::progressStep($unit->progress);
    $progressIndex = $progressStep ?? 0;
    $progressCancelled = $unit->status === 'cancelled';
@endphp
<ol class="bk-progress" aria-label="Progres unit {{ $unit->product_name }}{{ $unit->variantLabel() }}">
    @foreach(\App\Support\RentalLabels::PROGRESS_STEPS as $i => $step)
        @php
            // Unit batal tidak pernah "selesai": semua langkah ditahan pada
            // keadaan terakhir supaya tidak terbaca seolah pengerjaan lanjut.
            $state = $progressCancelled ? ($i < $progressIndex ? 'done' : 'todo') : ($i < $progressIndex ? 'done' : ($i === $progressIndex ? 'current' : 'todo'));
        @endphp
        <li class="bk-progress__item" data-state="{{ $state }}" @if($state === 'current') aria-current="step" @endif>
            <span class="bk-progress__dot" aria-hidden="true">@if($state === 'done')<span class="bk-i">@include('layouts.component._icon', ['name' => 'check', 'size' => 16])</span>@else{{ $i + 1 }}@endif</span>
            <span class="bk-progress__label">{{ \App\Support\RentalLabels::progress($step) }}</span>
        </li>
    @endforeach
</ol>
<p class="bk-progress__hint">
    @if($progressCancelled)
        Unit ini dibatalkan; alur pengerjaan berhenti di langkah tersebut.
    @else
        {{ \App\Support\RentalLabels::progressHint($unit->progress) }}
    @endif
</p>
