<?php

namespace App\Providers;

use App\Support\TrustedProxies;
use Illuminate\Http\Middleware\TrustProxies as TrustProxiesMiddleware;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Paginasi: satu rupa untuk semua peran. Tanpa ini `->links()` memakai
        // tampilan bawaan Laravel (kelas Tailwind) — CSS-nya tidak pernah
        // dibangun di proyek ini, sehingga nomor halaman tampil sebagai teks
        // polos tanpa sasaran sentuh 44 px. Berkas tampilannya memakai kelas
        // `.bk-page-nav*` yang dipetakan ke token tema dan panel admin.
        Paginator::defaultView('vendor.pagination.token-simple');
        Paginator::defaultSimpleView('vendor.pagination.token-simple');

        // Percayai header proxy (X-Forwarded-*) dari reverse proxy / load
        // balancer hosting (mis. Hostinger). Tanpa ini Laravel mengira koneksi
        // masih HTTP di belakang proxy → "Mixed Content" atau redirect berulang
        // saat HTTPS dipaksa.
        //
        // Dikerjakan di sini, bukan di `bootstrap/app.php`: closure
        // `withMiddleware` dijalankan sebelum konfigurasi dimuat, jadi
        // `config()` belum bisa dipanggil di sana. `TrustProxies::at()` adalah
        // pemanggilan yang sama yang dilakukan `$middleware->trustProxies()`.
        TrustProxiesMiddleware::at(config('app.trusted_proxies', TrustedProxies::DEFAULT));
    }
}
