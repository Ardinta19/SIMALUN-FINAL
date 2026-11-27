@php
    $admAsset = function (string $path) {
        $full = public_path($path);
        return asset($path).(file_exists($full) ? '?v='.filemtime($full) : '');
    };
    $admPageTitle = trim($__env->yieldContent('title', 'Admin'));
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $admPageTitle }} · Berkah Kreatif</title>
<meta name="color-scheme" content="light dark">
{{-- Panel admin tidak untuk mesin pencari: halaman memuat data pelanggan. --}}
<meta name="robots" content="noindex, nofollow">
<script>
    /* Tandai lebih awal: tanpa kelas ini semua konten reveal tetap terlihat,
       sehingga tidak ada bagian halaman yang hilang bila skrip gagal. */
    (function () {
        var root = document.documentElement;
        root.classList.add('adm-js');
        try {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                root.classList.add('adm-reduced');
            }
        } catch (e) {}
    })();
</script>
<link rel="stylesheet" href="{{ $admAsset('css/berkah-admin.css') }}">
<link rel="stylesheet" href="{{ $admAsset('css/page-transitions.css') }}">
<script src="{{ $admAsset('js/page-transitions.js') }}" defer></script>
@include('layouts.component._theme_head')
@stack('head')
</head>
<body class="adm-app">
<a class="adm-skip" href="#main-content">Langsung ke isi</a>

@include('layouts.component.admin._sidebar')
<div class="adm-backdrop" data-open="false" aria-hidden="true"></div>

<div class="adm-main">
    @include('layouts.component.admin._topbar')

    <main class="adm-content" id="main-content" tabindex="-1">
        @if($errors->any())
            <div class="adm-note adm-note--danger" role="alert" data-adm-reveal="page">
                <span class="adm-note__title">Mohon periksa kembali</span>
                <ul style="margin:6px 0 0;padding-left:1.2em">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

@include('layouts.component.admin._bottom_nav')

<div class="adm-toasts" aria-live="polite" aria-atomic="false">
    @if(session('success'))
        <div class="adm-toast" data-adm-toast data-adm-life="6000" role="status">
            <span class="adm-toast__icon" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'check', 'size' => 20])</span></span>
            <div class="adm-toast__body"><strong class="adm-toast__title">Berhasil</strong>{{ session('success') }}</div>
            <button type="button" class="adm-toast__close" data-adm-toast-close aria-label="Tutup pemberitahuan">&times;</button>
            <span class="adm-toast__bar" aria-hidden="true"></span>
        </div>
    @endif
    @if(session('error'))
        <div class="adm-toast adm-toast--danger" data-adm-toast data-adm-life="9000" role="alert">
            <span class="adm-toast__icon" aria-hidden="true">!</span>
            <div class="adm-toast__body"><strong class="adm-toast__title">Gagal diproses</strong>{{ session('error') }}</div>
            <button type="button" class="adm-toast__close" data-adm-toast-close aria-label="Tutup pemberitahuan">&times;</button>
            <span class="adm-toast__bar" aria-hidden="true"></span>
        </div>
    @endif
    @if(session('status'))
        <div class="adm-toast adm-toast--warn" data-adm-toast data-adm-life="7000" role="status">
            <span class="adm-toast__icon" aria-hidden="true">i</span>
            @php $statusKey = (string) session('status'); @endphp
            <div class="adm-toast__body"><strong class="adm-toast__title">Informasi</strong>{{ \Illuminate\Support\Facades\Lang::has('status.'.$statusKey) ? __('status.'.$statusKey) : $statusKey }}</div>
            <button type="button" class="adm-toast__close" data-adm-toast-close aria-label="Tutup pemberitahuan">&times;</button>
            <span class="adm-toast__bar" aria-hidden="true"></span>
        </div>
    @endif
</div>

@include('layouts.component._confirm_modal')
@include('layouts.component._ui_kit')
@include('layouts.component._form_controls')
<script src="{{ $admAsset('js/berkah-admin.js') }}" defer></script>
@stack('scripts')
</body>
</html>
