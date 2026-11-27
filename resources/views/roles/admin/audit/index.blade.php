@extends('layouts.admin')

@section('back', route('dashboard.admin'))
@section('title', 'Jejak audit')
@section('crumbs')<strong>Jejak audit</strong>@endsection

{{-- Dulu halaman mandiri dengan `<header class="appbar">` dan `_navbar_admin`.
     Sekarang memakai shell admin. Logika filter (prefix aksi, aktor, periode)
     dan isi catatan tidak berubah. --}}
@section('content')
<div class="adm-page-head" data-adm-reveal="head">
    <div class="adm-page-head__text">
        <span class="adm-kicker">Keamanan &amp; jejak</span>
        <h1>Jejak audit</h1>
        <p class="adm-page-sub">Setiap tindakan admin tercatat: siapa, kapan, dari IP mana, dan apa yang berubah.</p>
    </div>
    <div class="adm-actions">
        <span class="adm-badge">{{ $logs->total() }} catatan</span>
    </div>
</div>

@if($actionGroups->isNotEmpty())
    @php
        $prefixTotals = [];
        foreach ($actionGroups as $group) {
            $prefix = explode('.', $group->action)[0];
            $prefixTotals[$prefix] = ($prefixTotals[$prefix] ?? 0) + $group->total;
        }
    @endphp
    <div class="adm-chipbar" data-adm-reveal="chips" aria-label="Filter cepat berdasarkan aksi">
        <span class="adm-chipbar__label">Aksi</span>
        <a href="{{ route('admin.audit.index') }}"
           class="adm-chip adm-chip--link {{ ! request('action') ? 'is-active' : '' }}"
           @if(! request('action')) aria-current="true" @endif>
            Semua ({{ $logs->total() }})
        </a>
        @foreach($prefixTotals as $prefix => $prefixTotal)
            <a href="{{ route('admin.audit.index', ['action' => $prefix]) }}"
               class="adm-chip adm-chip--link {{ request('action') === $prefix ? 'is-active' : '' }}"
               @if(request('action') === $prefix) aria-current="true" @endif>
                {{ $prefix }} ({{ $prefixTotal }})
            </a>
        @endforeach
    </div>
@endif

<form class="adm-toolbar" method="GET" action="{{ route('admin.audit.index') }}" data-adm-reveal="filter" aria-label="Filter jejak audit">
    <div class="adm-field-grid">
        <div class="adm-field">
            <label for="actor">Aktor</label>
            <select name="actor_id" id="actor" data-fc-select data-fc-title="Pilih aktor">
                <option value="">Semua aktor</option>
                @foreach($actors as $actor)
                    <option value="{{ $actor->id }}" @selected((string) request('actor_id') === (string) $actor->id)>
                        {{ $actor->name }} ({{ $actor->role }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="adm-field">
            <label for="action-filter">Aksi</label>
            <input type="text" name="action" id="action-filter" maxlength="80"
                   value="{{ request('action') }}" placeholder="Contoh: voucher, pesanan.">
        </div>

        <div class="adm-field">
            <label for="date-from">Dari tanggal</label>
            <input type="date" name="date_from" id="date-from"
                   value="{{ request('date_from') }}" data-fc-date data-fc-title="Dari tanggal">
        </div>

        <div class="adm-field">
            <label for="date-to">Sampai tanggal</label>
            <input type="date" name="date_to" id="date-to"
                   value="{{ request('date_to') }}" data-fc-date data-fc-title="Sampai tanggal">
        </div>
    </div>

    <div class="adm-actions">
        <button type="submit" class="adm-btn">
            @include('layouts.component._icon', ['name' => 'check', 'size' => 16])
            Terapkan
        </button>
        <a href="{{ route('admin.audit.index') }}" class="adm-btn adm-btn--ghost">
            @include('layouts.component._icon', ['name' => 'refresh', 'size' => 16])
            Atur ulang filter
        </a>
    </div>
</form>

<section class="adm-card" data-adm-reveal="logs" aria-labelledby="adm-audit-list-title">
    <div class="adm-card__head">
        <h2 id="adm-audit-list-title">Catatan terbaru</h2>
        <span class="adm-help">Halaman {{ $logs->currentPage() }} dari {{ max(1, $logs->lastPage()) }}</span>
    </div>

    @forelse($logs as $log)
        <article class="adm-timeline__item adm-timeline-row">
            <div class="adm-row adm-row--between adm-row--tight">
                <span class="adm-badge adm-badge--info">{{ $log->action }}</span>
                <time class="adm-timeline__time" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</time>
            </div>

            @if($log->summary)
                <p class="adm-timeline__note adm-flat adm-wrapany">{{ $log->summary }}</p>
            @endif

            <p class="adm-help adm-flat adm-wrapany">
                <strong>Aktor:</strong> {{ $log->actor?->name ?? 'Sistem' }}
                @if($log->ip)· <strong>IP:</strong> {{ $log->ip }}@endif
                @if($log->auditable_type)· <strong>Target:</strong> {{ class_basename($log->auditable_type) }}#{{ $log->auditable_id }}@endif
            </p>

            @if($log->before || $log->after)
                @php
                    $beforeArr = is_array($log->before) ? $log->before : [];
                    $afterArr = is_array($log->after) ? $log->after : [];
                    $diffKeys = array_unique(array_merge(array_keys($beforeArr), array_keys($afterArr)));
                @endphp
                <div class="adm-stack adm-stack--snug-top">
                    @foreach($diffKeys as $diffKey)
                        @php
                            $before = $beforeArr[$diffKey] ?? null;
                            $after = $afterArr[$diffKey] ?? null;
                            if (is_array($before)) { $before = json_encode($before); }
                            if (is_array($after)) { $after = json_encode($after); }
                        @endphp
                        @continue($before === $after)
                        <div class="adm-row adm-row--tight adm-row--wrap">
                            <span class="adm-chip">{{ $diffKey }}</span>
                            @if($before !== null)
                                <span class="adm-chip adm-strike">{{ Str::limit((string) $before, 60) }}</span>
                                <span class="adm-ink-3" aria-hidden="true">→</span>
                            @endif
                            <span class="adm-chip adm-strong">{{ $after !== null ? Str::limit((string) $after, 60) : '(dihapus)' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    @empty
        <div class="adm-empty">
            <span class="adm-empty__icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'audit', 'size' => 32])</span>
            <p class="adm-empty__title">Belum ada catatan audit yang cocok</p>
            <p class="adm-help adm-flat">Coba ubah atau atur ulang filter di atas.</p>
            @if(request()->hasAny(['actor_id', 'action', 'date_from', 'date_to']))
                <a href="{{ route('admin.audit.index') }}" class="adm-btn adm-btn--ghost adm-btn--sm adm-mt-3">
                    @include('layouts.component._icon', ['name' => 'refresh', 'size' => 16])
                    Atur ulang filter
                </a>
            @endif
        </div>
    @endforelse

    @if($logs->hasPages())
        <div class="adm-row adm-row--center adm-mt-4">
            {{ $logs->links('vendor.pagination.token-simple') }}
        </div>
    @endif
</section>
@endsection
