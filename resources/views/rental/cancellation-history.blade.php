@if($unit->cancellationEvents->isNotEmpty())
<h3>Riwayat pembatalan dan kelanjutan</h3><ul>
@foreach($unit->cancellationEvents as $event)
<li>{{ $event->action === 'cancel' ? 'Dibatalkan' : 'Dilanjutkan' }} · {{ $event->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB · {{ $event->note }}</li>
@endforeach
</ul>
@endif
