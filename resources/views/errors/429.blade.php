@php
    $code = 429; $icon = 'clock'; $title = 'Terlalu banyak percobaan'; $accent = '#c98a3f';
    $message = 'Kamu mencoba terlalu sering dalam waktu singkat. Tunggu sebentar, lalu coba lagi.';
    $primary = ['url' => url('/'), 'label' => 'Ke beranda', 'icon' => 'home'];
    $secondary = ['url' => url('/'), 'label' => 'Coba lagi', 'icon' => 'refresh-cw', 'reload' => true];
    $tip = 'Batas ini melindungi akunmu dari percobaan masuk orang lain.';
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','secondary','tip'))
