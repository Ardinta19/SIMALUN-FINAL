{{--
|--------------------------------------------------------------------------
| Berkah Kreatif — UI kit (tombol tema + dialog sakelar)
|--------------------------------------------------------------------------
| Di-@include sekali di akhir setiap halaman (sebelum tag penutup body). Semua bagian
| dibungkus @once sehingga aman bila partial ini ikut ter-include dua kali.
|
| Tombol tema dipindahkan otomatis oleh JS ke slot yang tersedia:
|   · <=700 px : ke dalam menu radial (`[data-bk-theme-host="radial"]`) —
|                satu tombol, bukan dua;
|   · >700 px  : ke header (`[data-bk-theme-host]`, mis. header katalog);
|   · tanpa slot: tetap melayang di kanan bawah.
|--------------------------------------------------------------------------
--}}
@php
    $kitAsset = function (string $path) {
        $full = public_path($path);
        return asset($path) . (file_exists($full) ? '?v=' . filemtime($full) : '');
    };
@endphp
@once
<button type="button" class="bk-theme-btn bk-theme-dock" data-bk-theme-toggle aria-haspopup="dialog" aria-controls="bk-theme-dialog" aria-pressed="false" aria-label="Buka pengaturan tema" hidden>
    <svg class="bk-theme-btn__sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path>
    </svg>
    <svg class="bk-theme-btn__moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path>
    </svg>
</button>

<dialog class="bk-theme-dialog" id="bk-theme-dialog" aria-labelledby="bk-theme-title">
    <div class="bk-theme-dialog__body">
        <div class="bk-theme-dialog__head">
            <div>
                <span class="bk-theme-dialog__eyebrow">Tampilan</span>
                <h2 id="bk-theme-title">Terang atau gelap?</h2>
            </div>
            <button type="button" class="bk-theme-dialog__close" data-bk-close-theme aria-label="Tutup pengaturan tema">&times;</button>
        </div>

        <div class="bk-theme-dialog__stage">
            <svg id="bk-theme-switch" viewBox="0 0 440 240" fill="none" xmlns="http://www.w3.org/2000/svg" aria-checked="false" aria-label="Sakelar tema" tabindex="0" role="switch">
                <path d="M96 40l6 12 12 6-12 6-6 12-6-12-12-6 12-6z" fill="#d9a3b1" opacity=".55"></path>
                <path d="M388 176l4 9 9 4-9 4-4 9-4-9-9-4 9-4z" fill="#d9a3b1" opacity=".45"></path>
                <path d="M70 168c-14 6-24 16-30 28" stroke="#cf9fae" stroke-width="4" stroke-linecap="round" opacity=".5"></path>

                <rect class="bk-theme-svg__panel" x="112" y="68" width="272" height="112" rx="56" stroke="#5b2330" stroke-width="4"></rect>
                <rect class="bk-theme-svg__track" x="122" y="78" width="252" height="92" rx="46"></rect>

                <!-- Terang di kiri (matahari), gelap di kanan (bulan). Ikon aktif
                     sengaja lebih kecil dari kenop (r=38) supaya tertutup penuh. -->
                <g opacity=".85">
                    <g fill="#c98a3f" stroke="#c98a3f" stroke-width="3" stroke-linecap="round">
                        <circle cx="176" cy="124" r="12" stroke="none"></circle>
                        <path d="M176 106V101M176 142V147M158 124h-5M194 124h5M163 111l-4-4M189 111l4-4M163 137l-4 4M189 137l4 4"></path>
                    </g>
                    <circle cx="328" cy="124" r="17" fill="#9c7b86"></circle>
                    <circle class="bk-theme-svg__cut" cx="334" cy="124" r="14"></circle>
                </g>

                <circle class="bk-theme-svg__knob" cx="328" cy="124" r="38" data-knob-light="-152" data-knob-dark="0"></circle>
                <circle class="bk-theme-svg__knob-ring" cx="328" cy="124" r="38" fill="none" stroke="#5b2330" stroke-width="3" opacity=".35"></circle>

                <!-- Tangan robot selalu datang dari kanan: grup dalam
                     dicerminkan sekali (scale -1) untuk kedua tema. -->
                <g id="bk-theme-hand-wrap" opacity="0" aria-hidden="true">
                    <g id="bk-theme-hand-art">
                        <rect x="-168" y="106" width="188" height="38" rx="19" fill="#e9c6cf" stroke="#5b2330" stroke-width="4"></rect>
                        <rect x="-2" y="86" width="102" height="76" rx="26" fill="#f9e3e8" stroke="#5b2330" stroke-width="4"></rect>
                        <g stroke="#5b2330" stroke-width="4" stroke-linecap="round">
                            <path d="M20 104h58"></path>
                            <path d="M20 124h66"></path>
                            <path d="M20 144h56"></path>
                        </g>
                        <g id="bk-theme-thumb">
                            <rect x="16" y="62" width="58" height="24" rx="12" fill="#f9e3e8" stroke="#5b2330" stroke-width="4"></rect>
                        </g>
                    </g>
                </g>
            </svg>
        </div>

        <p class="bk-theme-dialog__hint">Ketuk sakelar untuk berganti tema. Pilihanmu diingat di perangkat ini.</p>
        <div class="bk-theme-choices">
            <button type="button" class="bk-theme-choice" data-bk-theme-set="light" aria-pressed="false">Terang</button>
            <button type="button" class="bk-theme-choice" data-bk-theme-set="dark" aria-pressed="false">Gelap</button>
            <button type="button" class="bk-theme-choice" data-bk-theme-auto aria-pressed="false">Ikuti perangkat</button>
        </div>
    </div>
</dialog>

{{-- Kembali pintar: dipakai semua tombol [data-smart-back] di seluruh situs. --}}
<script src="{{ $kitAsset('js/berkah-smart-back.js') }}"></script>
<script src="{{ $kitAsset('js/berkah-ui-kit.js') }}"></script>
@endonce
