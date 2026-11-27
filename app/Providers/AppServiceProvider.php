<?php

namespace App\Providers;

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
    }
}
