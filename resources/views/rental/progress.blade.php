@php
    $progressLabels = ['waiting' => 'Menunggu pengerjaan', 'production' => 'Dalam pengerjaan', 'ready' => 'Siap diserahkan', 'handed_over' => 'Sudah diserahkan', 'returned' => 'Sudah dikembalikan'];
    $nextLabels = ['waiting' => 'Mulai pengerjaan', 'production' => 'Tandai siap diserahkan', 'ready' => 'Tandai sudah diserahkan', 'handed_over' => 'Tandai sudah dikembalikan'];
@endphp
<h3>Perkembangan unit</h3>
<p>{{ $progressLabels[$unit->progress] ?? $unit->progress }}</p>
@if($unit->progressEvents->isNotEmpty())
<ul>@foreach($unit->progressEvents as $event)<li>{{ $progressLabels[$event->progress] ?? $event->progress }} · {{ $event->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB @if($admin) · {{ $event->recorder?->name ?? 'Sistem' }} @endif @if($event->note) — {{ $event->note }} @endif</li>@endforeach</ul>
@endif
@if($admin && $unit->status === 'confirmed' && isset($nextLabels[$unit->progress]))
<p class="muted">Pengerjaan memerlukan persetujuan preview terbaru, atau keputusan "lanjutkan dengan catatan" setelah tenggat respons desain lewat; serah terima memerlukan pembayaran lunas terverifikasi.</p>
<form class="stack" method="post" action="{{ route('admin.rental.progress.advance', $unit) }}">@csrf<input type="hidden" name="expected_progress" value="{{ $unit->progress }}"><label>Catatan perkembangan (opsional)<textarea name="note" maxlength="1000"></textarea></label><p><button class="btn secondary">{{ $nextLabels[$unit->progress] }}</button></p></form>
@endif
