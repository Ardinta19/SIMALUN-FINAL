{{--
    Tombol hapus permanen — dua langkah, tanpa `confirm()`.

    Proyek ini tidak memakai dialog `confirm()` di panel admin (tidak bisa
    digayakan, diblokir sebagian peramban, dan teksnya tidak bisa menjelaskan
    akibatnya). Sebagai gantinya: satu ketukan membuka penjelasan + tombol
    "Ya, hapus permanen". Bila `blocked` diisi, tombol tidak dirender sama
    sekali dan alasannya yang tampil — supaya admin tidak menekan tombol yang
    pasti gagal.

    Properti: action, note, label, confirmLabel, blocked.
--}}
@php
    $labelHapus = $label ?? 'Hapus';
    $tombolHapus = $confirmLabel ?? 'Ya, hapus permanen';
    $blocked = $blocked ?? null;
@endphp
@if($blocked)
    <p class="adm-help adm-danger-note">{{ $blocked }}</p>
@else
    <details class="adm-danger">
        <summary class="adm-btn adm-btn--ghost adm-btn--sm">{{ $labelHapus }}</summary>
        <div class="adm-danger__body">
            <p class="adm-danger__note">{{ $note }}</p>
            <form method="post" action="{{ $action }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">{{ $tombolHapus }}</button>
            </form>
        </div>
    </details>
@endif
