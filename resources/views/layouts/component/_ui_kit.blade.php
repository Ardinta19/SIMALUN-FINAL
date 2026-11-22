{{--
|--------------------------------------------------------------------------
| Berkah Kreatif — UI kit (tombol tema + dialog sakelar)
|--------------------------------------------------------------------------
| Di-@include sekali tepat sebelum </body> di setiap halaman. Semua bagian
| dibungkus @once sehingga aman bila partial ini ikut ter-include dua kali.
|
| Tombol tema dipindahkan otomatis oleh JS ke `[data-bk-theme-host]`
| (mis. header katalog) atau tetap melayang di kanan bawah bila halaman
| tidak punya slot header.
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
                <title>Sakelar tema terang dan gelap</title>
                <path d="M96 40l6 12 12 6-12 6-6 12-6-12-12-6 12-6z" fill="#d9a3b1" opacity=".55"></path>
                <path d="M388 176l4 9 9 4-9 4-4 9-4-9-9-4 9-4z" fill="#d9a3b1" opacity=".45"></path>
                <path d="M70 168c-14 6-24 16-30 28" stroke="#cf9fae" stroke-width="4" stroke-linecap="round" opacity=".5"></path>

                <rect class="bk-theme-svg__panel" x="112" y="68" width="272" height="112" rx="56" stroke="#5b2330" stroke-width="4"></rect>
                <rect class="bk-theme-svg__track" x="122" y="78" width="252" height="92" rx="46"></rect>

                <g opacity=".85">
                    <path d="M186 100a22 22 0 1 0 0 44 19 19 0 1 1 0-44z" fill="#9c7b86"></path>
                    <circle cx="316" cy="122" r="17" fill="#c98a3f"></circle>
                    <g stroke="#c98a3f" stroke-width="3" stroke-linecap="round">
                        <path d="M316 96v-7M316 155v-7M290 122h-7M349 122h-7M297 103l-5-5M340 146l-5-5M297 141l-5 5M340 98l-5 5"></path>
                    </g>
                </g>

                <circle class="bk-theme-svg__knob" cx="328" cy="124" r="38"></circle>
                <circle cx="328" cy="124" r="38" stroke="#5b2330" stroke-width="3" opacity=".35"></circle>

                <g id="bk-theme-hand-wrap" opacity="0" aria-hidden="true">
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

<script src="{{ $kitAsset('js/berkah-ui-kit.js') }}"></script>
@endonce
