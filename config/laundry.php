<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Informasi Usaha
    |--------------------------------------------------------------------------
    |
    | Kunci `LAUNDRY_*` lama TIDAK lagi dibaca. Nilai lama di `.env` (mis.
    | "Azka Laundry", alamat dan nomor laundry) dulu bocor ke footer dan
    | email karena Laravel memuat `.env` pengembang saat menjalankan tes,
    | sehingga tampilan bisa berbeda antara mesin yang sudah diperbarui dan
    | yang belum. Kunci `BUSINESS_*` membuat merek yang tampil tidak lagi
    | bergantung pada sisa konfigurasi aplikasi sebelumnya.
    */

    'name' => env('BUSINESS_NAME', 'Berkah Kreatif'),
    'tagline' => env('BUSINESS_TAGLINE', 'Papan bunga untuk setiap cerita'),

    /*
    |--------------------------------------------------------------------------
    | Alamat & Lokasi
    |--------------------------------------------------------------------------
    */

    // Usaha ini belum punya outlet resmi, jadi alamat jalan dan tautan peta
    // sengaja kosong: menampilkan alamat karangan lebih buruk daripada tidak
    // menampilkan apa pun. Lokasi serah terima disepakati lewat WhatsApp.
    'address' => env('BUSINESS_ADDRESS', ''),
    'google_maps' => env('BUSINESS_GMAPS', ''),

    /*
    |--------------------------------------------------------------------------
    | Kontak
    |--------------------------------------------------------------------------
    */

    'phone_display' => env('BUSINESS_PHONE_DISPLAY', '0895-1405-7305'),
    'whatsapp' => env('BUSINESS_WA', '6289514057305'),

    /*
    |--------------------------------------------------------------------------
    | Media Sosial
    |--------------------------------------------------------------------------
    |
    | Ditampilkan di kaki halaman katalog dan halaman bantuan. Kosongkan
    | bila akun belum ada; tautannya otomatis disembunyikan.
    */

    'instagram' => env('BUSINESS_INSTAGRAM', 'https://www.instagram.com/berkahkreatif_/'),
    'tiktok' => env('BUSINESS_TIKTOK', 'https://www.tiktok.com/@berkahkreatif_'),

];
