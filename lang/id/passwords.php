<?php

/*
|--------------------------------------------------------------------------
| Pesan pemulihan kata sandi
|--------------------------------------------------------------------------
| `Password::reset()` mengembalikan kunci seperti `passwords.token` dan
| `passwords.throttled`; halaman reset menampilkannya lewat `__($status)`.
| Tanpa berkas ini pengguna membaca kalimat Inggris (atau kunci mentah).
*/

return [
    'reset' => 'Kata sandi kamu sudah diperbarui. Silakan masuk dengan kata sandi baru.',
    'sent' => 'Jika email terdaftar, tautan pemulihan telah dikirim. Periksa kotak masuk dan folder spam.',
    'throttled' => 'Terlalu banyak permintaan. Tunggu sebentar sebelum mencoba lagi.',
    'token' => 'Tautan pemulihan tidak berlaku atau sudah kedaluwarsa. Minta tautan baru.',
    'user' => 'Kami tidak menemukan akun dengan email tersebut.',
];
