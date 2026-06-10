{{--
    Section "Analitik 30 Hari" — versi chart (donut, radial gauge, bar bergradien).
    Semua chart digambar manual via SVG (tanpa library) supaya ringan & bespoke.

    Variabel dari controller:
      $topServices, $totalOrder30d, $pickupBuckets,
      $topCustomers, $ratingStats, $ulasanTerbaru, $voucherAktif
--}}

@php
    // ── Donut: distribusi layanan ────────────────────────────────
    $svcPalette = ['#0077b6', '#48cae4', '#FF6B35', '#00C48C', '#f59e0b'];
    $top5Sum    = (int) $topServices->sum('total_order');
    $grandTotal = max((int) ($totalOrder30d ?? 0), $top5Sum);
    $lainnya    = max(0, $grandTotal - $top5Sum);

    $segments = [];
    foreach ($topServices as $i => $svc) {
        $segments[] = [
            'name'  => $svc->service?->name ?? 'Layanan dihapus',
            'value' => (int) $svc->total_order,
            'color' => $svcPalette[$i] ?? '#94a3b8',
        ];
    }
    if ($lainnya > 0 && $grandTotal > 0) {
        $segments[] = ['name' => 'Layanan lainnya', 'value' => $lainnya, 'color' => '#cbd5e1'];
    }

    $C = 2 * M_PI * 46;          // keliling lingkaran donut (r=46)
    $cumPrev = 0;
    $donutData = [];
    foreach ($segments as $seg) {
        $frac = $grandTotal > 0 ? $seg['value'] / $grandTotal : 0;
        $len  = $frac * $C;
        $donutData[] = [
            'color'   => $seg['color'],
            'name'    => $seg['name'],
            'value'   => $seg['value'],
            'pct'     => $grandTotal > 0 ? round($frac * 100) : 0,
            'len'     => $len,
            'offset'  => -$cumPrev,
        ];
        $cumPrev += $len;
    }
    $svcLeader = $donutData[0] ?? null;

    // ── Pickup ───────────────────────────────────────────────────
    $pickupLabels = ['pagi' => 'Pagi', 'siang' => 'Siang', 'sore' => 'Sore'];
    $maxPickup    = max($pickupBuckets) ?: 1;
    $sumPickup    = array_sum($pickupBuckets);
    $peakSlot     = $sumPickup > 0 ? array_search(max($pickupBuckets), $pickupBuckets) : null;

    // ── Rating ───────────────────────────────────────────────────
    $avgRating   = $ratingStats?->avg_rating ? (float) $ratingStats->avg_rating : null;
    $totalRating = $ratingStats?->total_rating ?? 0;
    $gaugeC      = 2 * M_PI * 30;
    $gaugeLen    = $avgRating !== null ? ($avgRating / 5) * $gaugeC : 0;
@endphp

<style>
.an { margin-top: 24px; }
.an__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 13px; padding: 0 2px; }
.an__title { font-size: 1rem; font-weight: 800; color: var(--ink); }
.an__sub { font-size: 0.7rem; font-weight: 700; color: var(--ink-lt); margin-top: 2px; }
.an__pill { display: inline-flex; align-items: center; gap: 5px; padding: 6px 11px; background: var(--orange-lt); color: #c2410c; border-radius: 999px; font-size: 0.7rem; font-weight: 800; }
.an__pill svg { width: 13px; height: 13px; }

.an-grid { display: grid; gap: 12px; }
.an-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 17px; box-shadow: var(--shadow); }
.an-card__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
.an-card__name { display: flex; align-items: center; gap: 9px; font-weight: 800; font-size: 0.9rem; color: var(--ink); }
.an-card__ico { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.an-card__ico svg { width: 17px; height: 17px; }
.an-card__tag { font-size: 0.62rem; font-weight: 800; color: var(--ink-lt); text-transform: uppercase; letter-spacing: 0.5px; }
.an-empty { padding: 24px 8px; text-align: center; color: var(--ink-lt); font-size: 0.8rem; font-weight: 700; }
.an-insight { margin-top: 14px; padding: 10px 12px; background: var(--line-soft); border-radius: 11px; font-size: 0.72rem; font-weight: 600; color: var(--ink-mid); line-height: 1.4; }
.an-insight b { color: var(--ink); font-weight: 800; }

/* ── Donut ── */
.donut-wrap { display: flex; align-items: center; gap: 18px; }
.donut { width: 124px; height: 124px; flex-shrink: 0; position: relative; }
.donut svg { width: 124px; height: 124px; transform: rotate(0deg); }
.donut__seg { transition: stroke-width 0.2s; }
.donut__center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.donut__num { font-size: 1.5rem; font-weight: 800; color: var(--ink); line-height: 1; }
.donut__cap { font-size: 0.56rem; font-weight: 800; color: var(--ink-lt); text-transform: uppercase; letter-spacing: 0.6px; margin-top: 3px; }
.legend { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 9px; }
.legend__row { display: flex; align-items: center; gap: 9px; }
.legend__dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.legend__name { flex: 1; min-width: 0; font-size: 0.78rem; font-weight: 700; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.legend__val { font-size: 0.76rem; font-weight: 800; color: var(--ink-mid); }
.legend__pct { font-size: 0.64rem; font-weight: 700; color: var(--ink-lt); min-width: 30px; text-align: right; }

/* ── Pickup bars ── */
.pk { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; align-items: end; height: 132px; padding-top: 6px; }
.pk__col { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 8px; }
.pk__track { width: 100%; height: 100%; display: flex; align-items: flex-end; justify-content: center; background: linear-gradient(180deg, transparent, var(--line-soft)); border-radius: 12px; }
.pk__bar { width: 70%; max-width: 46px; border-radius: 10px 10px 4px 4px; background: linear-gradient(180deg, #48cae4 0%, var(--blue) 100%); display: flex; align-items: flex-start; justify-content: center; padding-top: 6px; min-height: 6px; position: relative; animation: pkGrow 0.7s cubic-bezier(0.22,0.61,0.36,1) both; }
.pk__bar--peak { background: linear-gradient(180deg, #ff9a6a 0%, var(--orange) 100%); box-shadow: 0 6px 16px rgba(255,107,53,0.3); }
@keyframes pkGrow { from { transform: scaleY(0); } to { transform: scaleY(1); } }
.pk__bar { transform-origin: bottom; }
.pk__count { font-size: 0.74rem; font-weight: 800; color: #fff; }
.pk__lbl { font-size: 0.72rem; font-weight: 800; color: var(--ink-mid); }
.pk__lbl--peak { color: var(--orange); }

/* ── Rating ── */
.rate { display: flex; align-items: center; gap: 18px; }
.gauge { width: 86px; height: 86px; position: relative; flex-shrink: 0; }
.gauge svg { width: 86px; height: 86px; }
.gauge__center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.gauge__num { font-size: 1.45rem; font-weight: 800; color: var(--ink); line-height: 1; }
.gauge__max { font-size: 0.6rem; font-weight: 700; color: var(--ink-lt); }
.rate__info { flex: 1; }
.rate__stars { color: var(--amber); font-size: 1.05rem; letter-spacing: 2px; }
.rate__stars .off { color: #e2e8f0; }
.rate__count { font-size: 0.74rem; font-weight: 700; color: var(--ink-mid); margin-top: 5px; }
.reviews { margin-top: 15px; padding-top: 14px; border-top: 1px dashed var(--border); display: flex; flex-direction: column; gap: 12px; }
.review { display: flex; gap: 11px; }
.review__av { width: 34px; height: 34px; border-radius: 10px; background: var(--blue-lt); color: var(--blue-dark); font-weight: 800; font-size: 0.82rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.review__body { flex: 1; min-width: 0; }
.review__top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.review__name { font-size: 0.78rem; font-weight: 800; color: var(--ink); }
.review__rate { color: var(--amber); font-size: 0.74rem; letter-spacing: 1px; flex-shrink: 0; }
.review__comment { font-size: 0.74rem; color: var(--ink-mid); line-height: 1.45; margin-top: 3px; }
.review__code { font-size: 0.62rem; color: var(--ink-lt); font-weight: 700; margin-top: 4px; }

/* ── Top customers ── */
.cust { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px dashed var(--border); }
.cust:last-child { border-bottom: none; padding-bottom: 0; }
.cust:first-child { padding-top: 2px; }
.cust__rank { width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 900; flex-shrink: 0; background: var(--line-soft); color: var(--ink-lt); }
.cust__rank--1 { background: linear-gradient(145deg,#fde68a,#f59e0b); color: #fff; }
.cust__rank--2 { background: linear-gradient(145deg,#e2e8f0,#94a3b8); color: #fff; }
.cust__rank--3 { background: linear-gradient(145deg,#fbcfa3,#d97742); color: #fff; }
.cust__info { flex: 1; min-width: 0; }
.cust__name { font-size: 0.82rem; font-weight: 800; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cust__phone { font-size: 0.68rem; color: var(--ink-lt); font-weight: 600; margin-top: 1px; }
.cust__right { text-align: right; flex-shrink: 0; }
.cust__spent { font-size: 0.82rem; font-weight: 800; color: var(--green); }
.cust__orders { font-size: 0.64rem; color: var(--ink-lt); font-weight: 700; margin-top: 1px; }
</style>

<section class="an js-in">
    <div class="an__head">
        <div>
            <div class="an__title">Analitik Operasional</div>
            <div class="an__sub">Ringkasan 30 hari terakhir</div>
        </div>
        @if($voucherAktif > 0)
            <span class="an__pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v14"/></svg>
                {{ $voucherAktif }} voucher aktif
            </span>
        @endif
    </div>

    <div class="an-grid">

        {{-- ═══ Donut: Distribusi Layanan ═══ --}}
        <div class="an-card">
            <div class="an-card__head">
                <div class="an-card__name">
                    <span class="an-card__ico" style="background:var(--blue-lt);color:var(--blue);" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12c.552 0 1.005-.449.95-.998a10 10 0 0 0-8.953-8.951c-.55-.055-.998.398-.998.95v8a1 1 0 0 0 1 1z"/><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/></svg>
                    </span>
                    Distribusi Layanan
                </div>
                <span class="an-card__tag">Porsi Order</span>
            </div>

            @if($grandTotal > 0 && count($donutData))
                <div class="donut-wrap">
                    <div class="donut" aria-hidden="true">
                        <svg viewBox="0 0 124 124">
                            <circle cx="62" cy="62" r="46" fill="none" stroke="#eef4fa" stroke-width="15"/>
                            @foreach($donutData as $d)
                                <circle class="donut__seg" cx="62" cy="62" r="46" fill="none"
                                    stroke="{{ $d['color'] }}" stroke-width="15"
                                    stroke-dasharray="{{ $d['len'] }} {{ $C - $d['len'] }}"
                                    stroke-dashoffset="{{ $d['offset'] }}"
                                    transform="rotate(-90 62 62)" stroke-linecap="butt"/>
                            @endforeach
                        </svg>
                        <div class="donut__center">
                            <div class="donut__num">{{ $grandTotal }}</div>
                            <div class="donut__cap">Pesanan</div>
                        </div>
                    </div>
                    <div class="legend">
                        @foreach($donutData as $d)
                            <div class="legend__row">
                                <span class="legend__dot" style="background:{{ $d['color'] }}"></span>
                                <span class="legend__name">{{ $d['name'] }}</span>
                                <span class="legend__val">{{ $d['value'] }}</span>
                                <span class="legend__pct">{{ $d['pct'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if($svcLeader)
                    <div class="an-insight">
                        <b>{{ $svcLeader['name'] }}</b> jadi favorit pelanggan, mengisi <b>{{ $svcLeader['pct'] }}%</b> dari total order bulan ini.
                    </div>
                @endif
            @else
                <div class="an-empty">Belum ada pesanan dalam 30 hari terakhir.</div>
            @endif
        </div>

        {{-- ═══ Bar: Jam Pickup ═══ --}}
        <div class="an-card">
            <div class="an-card__head">
                <div class="an-card__name">
                    <span class="an-card__ico" style="background:var(--orange-lt);color:var(--orange);" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </span>
                    Jam Pickup Terpadat
                </div>
                <span class="an-card__tag">Atur Kurir</span>
            </div>

            @if($sumPickup > 0)
                <div class="pk">
                    @foreach($pickupBuckets as $slot => $count)
                        @php $isPeak = ($slot === $peakSlot && $count > 0); @endphp
                        <div class="pk__col">
                            <div class="pk__track">
                                <div class="pk__bar {{ $isPeak ? 'pk__bar--peak' : '' }}" style="height: {{ max(8, ($count / $maxPickup) * 100) }}%;">
                                    @if($count > 0)<span class="pk__count">{{ $count }}</span>@endif
                                </div>
                            </div>
                            <span class="pk__lbl {{ $isPeak ? 'pk__lbl--peak' : '' }}">{{ $pickupLabels[$slot] }}</span>
                        </div>
                    @endforeach
                </div>
                @if($peakSlot)
                    <div class="an-insight">
                        Jemput paling ramai di <b>{{ $pickupLabels[$peakSlot] }}</b> hari pastikan kurir cukup di jam ini.
                    </div>
                @endif
            @else
                <div class="an-empty">Belum ada data jadwal pickup.</div>
            @endif
        </div>

        {{-- ═══ Gauge: Rating & Ulasan ═══ --}}
        <div class="an-card">
            <div class="an-card__head">
                <div class="an-card__name">
                    <span class="an-card__ico" style="background:#fff7e6;color:#d97706;" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M11.5 3.2 13.7 8l5.3.5c.8 0 1.1 1 .5 1.5l-4 3.5 1.2 5.2c.2.8-.7 1.4-1.4 1l-4.6-2.8-4.6 2.8c-.7.4-1.6-.2-1.4-1l1.2-5.2-4-3.5c-.6-.5-.3-1.5.5-1.5L10.3 8l2.2-4.8z"/></svg>
                    </span>
                    Kepuasan Pelanggan
                </div>
                <span class="an-card__tag">Rata-rata</span>
            </div>

            @if($avgRating !== null)
                <div class="rate">
                    <div class="gauge" aria-hidden="true">
                        <svg viewBox="0 0 86 86">
                            <circle cx="43" cy="43" r="30" fill="none" stroke="#f1f5f9" stroke-width="9"/>
                            <circle cx="43" cy="43" r="30" fill="none" stroke="#f59e0b" stroke-width="9"
                                stroke-linecap="round"
                                stroke-dasharray="{{ $gaugeLen }} {{ $gaugeC - $gaugeLen }}"
                                transform="rotate(-90 43 43)"/>
                        </svg>
                        <div class="gauge__center">
                            <div class="gauge__num">{{ number_format($avgRating, 1) }}</div>
                            <div class="gauge__max">dari 5.0</div>
                        </div>
                    </div>
                    <div class="rate__info">
                        <div class="rate__stars">
                            @for($i = 1; $i <= 5; $i++)<span class="{{ $i <= round($avgRating) ? '' : 'off' }}">★</span>@endfor
                        </div>
                        <div class="rate__count">Berdasarkan {{ $totalRating }} ulasan pelanggan</div>
                    </div>
                </div>

                @if($ulasanTerbaru->count())
                    <div class="reviews">
                        @foreach($ulasanTerbaru as $u)
                            @php $un = $u->customer?->name ?? 'Customer'; @endphp
                            <div class="review">
                                <div class="review__av" aria-hidden="true">{{ mb_strtoupper(mb_substr($un, 0, 1)) }}</div>
                                <div class="review__body">
                                    <div class="review__top">
                                        <span class="review__name">{{ Str::limit($un, 18) }}</span>
                                        <span class="review__rate">@for($i=1;$i<=5;$i++){{ $i <= $u->rating ? '★' : '☆' }}@endfor</span>
                                    </div>
                                    @if($u->comment)
                                        <div class="review__comment">“{{ Str::limit($u->comment, 90) }}”</div>
                                    @endif
                                    <div class="review__code">#{{ strtoupper($u->order?->order_code ?? '—') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="an-empty">Belum ada rating yang masuk.</div>
            @endif
        </div>

        {{-- ═══ Top Customers ═══ --}}
        <div class="an-card">
            <div class="an-card__head">
                <div class="an-card__name">
                    <span class="an-card__ico" style="background:var(--green-lt);color:var(--green);" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    Pelanggan Setia
                </div>
                <span class="an-card__tag">Top 5</span>
            </div>

            @forelse($topCustomers as $i => $row)
                @php $nm = $row->customer?->name ?? 'Customer dihapus'; @endphp
                <div class="cust">
                    <div class="cust__rank cust__rank--{{ $i + 1 }}">{{ $i + 1 }}</div>
                    <div class="cust__info">
                        <div class="cust__name">{{ $nm }}</div>
                        <div class="cust__phone">{{ $row->customer?->phone ?? '—' }}</div>
                    </div>
                    <div class="cust__right">
                        <div class="cust__spent">Rp {{ number_format($row->total_spent ?? 0, 0, ',', '.') }}</div>
                        <div class="cust__orders">{{ $row->total_order }} pesanan</div>
                    </div>
                </div>
            @empty
                <div class="an-empty">Belum ada pelanggan aktif dalam 30 hari terakhir.</div>
            @endforelse
        </div>

    </div>
</section>
