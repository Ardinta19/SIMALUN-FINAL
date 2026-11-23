@php
    $code = 500; $icon = 'alert-triangle'; $title = 'Server Error'; $accent = '#b6435e';
    $message = 'Ada gangguan di server kami. Tim teknis sudah dikabari, coba lagi sebentar.';
    $primary = ['url' => url('/'), 'label' => 'Kembali ke Beranda', 'icon' => 'home'];
    $secondary = ['url' => 'javascript:location.reload()', 'label' => 'Coba Muat Ulang', 'icon' => 'refresh-cw'];
    $tip = 'Jika masalah terus terjadi, hubungi admin Berkah Kreatif.';
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','secondary','tip'))
