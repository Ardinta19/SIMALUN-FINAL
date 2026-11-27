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

    'address' => env('BUSINESS_ADDRESS', 'Mayang Mangurai, Kota Baru, Jambi City, Jambi'),
    'google_maps' => env('BUSINESS_GMAPS', 'https://maps.app.goo.gl/gArdhsRxYnPkRwDq8'),

    /*
    |--------------------------------------------------------------------------
    | Kontak
    |--------------------------------------------------------------------------
    */

    'phone_display' => env('BUSINESS_PHONE_DISPLAY', '0823-7888-6053'),
    'whatsapp' => env('BUSINESS_WA', '6282378886053'),

];
