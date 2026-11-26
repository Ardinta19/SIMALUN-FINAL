@php
    $code = 503; $icon = 'settings'; $title = 'Sedang pemeliharaan'; $accent = '#b6435e';
    $message = $exception?->getMessage() ?: 'Aplikasi sedang diperbarui. Mohon kembali beberapa saat lagi.';
    $primary = ['url' => url('/'), 'label' => 'Cek lagi', 'icon' => 'refresh-cw', 'reload' => true];
    $tip = 'Biasanya hanya beberapa menit.';
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','tip'))
