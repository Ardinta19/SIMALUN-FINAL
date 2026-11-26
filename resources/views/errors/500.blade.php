@php
    $code = 500; $icon = 'alert-triangle'; $title = 'Kesalahan server'; $accent = '#b6435e';
    $message = 'Ada gangguan di server kami. Tim teknis sudah dikabari, coba lagi sebentar.';
    $primary = ['url' => url('/'), 'label' => 'Kembali ke beranda', 'icon' => 'home'];
    $secondary = ['url' => url('/'), 'label' => 'Coba muat ulang', 'icon' => 'refresh-cw', 'reload' => true];
    $tip = 'Jika masalah terus terjadi, hubungi admin Berkah Kreatif.';
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','secondary','tip'))
