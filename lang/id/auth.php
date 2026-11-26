<?php

/*
|--------------------------------------------------------------------------
| Pesan autentikasi
|--------------------------------------------------------------------------
| Tanpa berkas ini, `__('auth.failed')` dan `__('auth.password')` jatuh ke
| `fallback_locale` (en) sehingga halaman konfirmasi kata sandi menampilkan
| kalimat Inggris di tengah antarmuka Indonesia.
*/

return [
    'failed' => 'Email atau kata sandi tidak cocok dengan data kami.',
    'password' => 'Kata sandi yang kamu masukkan salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Coba lagi dalam :seconds detik.',
];
