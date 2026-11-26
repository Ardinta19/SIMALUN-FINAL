<?php

namespace App\Providers;

use App\Support\Laundry;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('laundry', fn () => new Laundry);
    }

    public function boot(): void
    {
        // Paginasi: satu rupa untuk semua peran. Tanpa ini `->links()` memakai
        // tampilan bawaan Laravel (kelas Tailwind) — CSS-nya tidak pernah
        // dibangun di proyek ini, sehingga nomor halaman tampil sebagai teks
        // polos tanpa sasaran sentuh 44 px. Berkas tampilannya memakai kelas
        // `.bk-page-nav*` yang dipetakan ke token tema dan panel admin.
        Paginator::defaultView('vendor.pagination.token-simple');
        Paginator::defaultSimpleView('vendor.pagination.token-simple');

        // Share laundry config ke semua views
        View::share('laundry', app('laundry'));

        // Blade directive: @laundryName, @laundryPhone dll
        Blade::directive('laundryName', fn () => '<?php echo e(\\App\\Support\\Laundry::name()); ?>');
        Blade::directive('laundryAddress', fn () => '<?php echo e(\\App\\Support\\Laundry::address()); ?>');
        Blade::directive('laundryPhone', fn () => '<?php echo e(\\App\\Support\\Laundry::phoneDisplay()); ?>');
    }
}
