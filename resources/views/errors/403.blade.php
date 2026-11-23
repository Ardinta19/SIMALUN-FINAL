@php
    $code = 403; $icon = 'lock-keyhole'; $title = 'Akses Ditolak'; $accent = '#b6435e';
    $message = $exception?->getMessage() ?: 'Halaman ini bukan untuk akun kamu. Silakan kembali ke area yang sesuai.';
    $primary = auth()->check() ? ['url' => route('dashboard'), 'label' => 'Ke Dashboard', 'icon' => 'home'] : ['url' => route('login'), 'label' => 'Masuk Dulu', 'icon' => 'key-round'];
    $secondary = ['url' => 'javascript:history.back()', 'label' => 'Kembali', 'icon' => 'undo-2'];
    $tip = 'Jika kamu yakin seharusnya punya akses, hubungi admin.';
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','secondary','tip'))
