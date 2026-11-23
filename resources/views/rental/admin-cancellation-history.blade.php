@if($unit->cancellationEvents->isNotEmpty())
    <h3>Riwayat pembatalan dan kelanjutan</h3>
    <ul class="adm-timeline">
        @foreach($unit->cancellationEvents as $event)
            <li class="adm-timeline__item is-done">
                <span class="adm-timeline__time">{{ $event->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span>
                <span class="adm-timeline__title">{{ $event->action === 'cancel' ? 'Dibatalkan' : 'Dilanjutkan' }}</span>
                @if($event->note)<span class="adm-timeline__note">{{ $event->note }}</span>@endif
            </li>
        @endforeach
    </ul>
@endif
