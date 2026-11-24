@php
    $progressLabels = ['waiting' => 'Menunggu pengerjaan', 'production' => 'Dalam pengerjaan', 'ready' => 'Siap diserahkan', 'handed_over' => 'Sudah diserahkan', 'returned' => 'Sudah dikembalikan'];
    $nextLabels = ['waiting' => 'Mulai pengerjaan', 'production' => 'Tandai siap diserahkan', 'ready' => 'Tandai sudah diserahkan', 'handed_over' => 'Tandai sudah dikembalikan'];
    $progressOrder = ['waiting', 'production', 'ready', 'handed_over', 'returned'];
    $currentIndex = array_search($unit->progress, $progressOrder, true);
@endphp
<h3>Perkembangan unit</h3>
<p style="margin:0 0 12px">
    <span class="adm-badge adm-badge--info adm-badge--live">{{ $progressLabels[$unit->progress] ?? $unit->progress }}</span>
</p>

<ol class="adm-timeline" aria-label="Tahapan perkembangan unit">
    @foreach($progressOrder as $stepIndex => $step)
        @php
            $state = $currentIndex === false ? 'todo' : ($stepIndex < $currentIndex ? 'done' : ($stepIndex === $currentIndex ? 'current' : 'todo'));
        @endphp
        <li class="adm-timeline__item {{ $state === 'done' ? 'is-done' : ($state === 'current' ? 'is-current' : '') }}">
            <span class="adm-timeline__title">{{ $progressLabels[$step] }}</span>
            <span class="adm-timeline__note">{{ $state === 'done' ? 'Selesai' : ($state === 'current' ? 'Tahap sekarang' : 'Belum dimulai') }}</span>
        </li>
    @endforeach
</ol>

@if($unit->progressEvents->isNotEmpty())
    <h4 style="margin:16px 0 8px">Riwayat tercatat</h4>
    <ul class="adm-timeline">
        @foreach($unit->progressEvents as $event)
            <li class="adm-timeline__item is-done">
                <span class="adm-timeline__time">{{ $event->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB @if($admin) · {{ $event->recorder?->name ?? 'Sistem' }} @endif</span>
                <span class="adm-timeline__title">{{ $progressLabels[$event->progress] ?? $event->progress }}</span>
                @if($event->note)<span class="adm-timeline__note">{{ $event->note }}</span>@endif
            </li>
        @endforeach
    </ul>
@endif

@if($admin && $unit->status === 'confirmed' && isset($nextLabels[$unit->progress]))
    <p class="adm-help" style="margin-top:12px">Pengerjaan memerlukan persetujuan pratinjau terbaru, atau keputusan "lanjutkan dengan catatan" setelah tenggat respons desain lewat; serah terima memerlukan pembayaran lunas terverifikasi.</p>
    <form class="adm-stack" method="post" action="{{ route('admin.rental.progress.advance', $unit) }}">
        @csrf
        <input type="hidden" name="expected_progress" value="{{ $unit->progress }}">
        <div class="adm-field">
            <label for="progress-note-{{ $unit->id }}">Catatan perkembangan (opsional)</label>
            <textarea id="progress-note-{{ $unit->id }}" name="note" maxlength="1000"></textarea>
        </div>
        <div><button class="btn secondary" type="submit">{{ $nextLabels[$unit->progress] }}</button></div>
    </form>
@endif
