{{--
    Ringkasan filter yang sedang berlaku.

    Dipakai semua halaman admin yang punya form filter, supaya tidak ada lagi
    keadaan "kolom berkata November, daftar berkata Januari": setiap filter yang
    aktif ditulis ulang sebagai chip, dan tiap chip bisa dilepas sendiri tanpa
    harus menekan "Atur ulang filter" (yang menghapus semuanya).

    Parameter:
      $chips      array  daftar ['label' => string, 'remove' => url|null]
      $resetUrl   string tautan "Hapus semua" (opsional)
--}}
@php
    $chipsAktif = collect($chips ?? [])->filter(fn ($chip) => filled($chip['label'] ?? null))->values();
@endphp
@if($chipsAktif->isNotEmpty())
    <div class="adm-filterstate" data-adm-reveal="filterstate" role="group" aria-label="Filter yang sedang berlaku">
        <span class="adm-filterstate__label">Filter aktif</span>
        @foreach($chipsAktif as $chip)
            <span class="adm-filterstate__chip">
                {{ $chip['label'] }}
                @if(filled($chip['remove'] ?? null))
                    <a class="adm-filterstate__x" href="{{ $chip['remove'] }}" aria-label="Hapus filter {{ $chip['label'] }}">&times;</a>
                @endif
            </span>
        @endforeach
        @if(filled($resetUrl ?? null))
            <a class="adm-filterstate__reset" href="{{ $resetUrl }}">Hapus semua filter</a>
        @endif
    </div>
@endif
