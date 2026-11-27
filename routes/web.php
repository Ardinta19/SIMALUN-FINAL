<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalCatalogController;
use App\Http\Controllers\RentalFeedbackController;
use App\Http\Controllers\RentalCartController;
use App\Http\Controllers\RentalOrderController;
use App\Http\Controllers\RentalDesignController;
use App\Http\Controllers\RentalPaymentController;
use App\Http\Controllers\RentalProgressController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\RentalAddonController;
use App\Http\Controllers\RentalUniversityController;
use App\Http\Controllers\RentalVoucherController;
use App\Http\Controllers\RentalCashBookController;
use App\Http\Controllers\RentalPaymentSettingController;
use App\Http\Controllers\RentalScheduleController;
use App\Http\Controllers\RentalReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RentalCatalogController::class, 'index'])->name('welcome');
Route::get('/katalog/{product}', [RentalCatalogController::class, 'show'])->name('catalog.show');
// Peta situs untuk mesin pencari: hanya halaman etalase yang terbuka.
Route::get('/sitemap.xml', [RentalCatalogController::class, 'sitemap'])->name('sitemap');
Route::get('/katalog/{product}/ketersediaan', [RentalCatalogController::class, 'availability'])->middleware('throttle:60,1')->name('catalog.availability');
Route::get('/apresiasi/{token}', [RentalFeedbackController::class, 'publicShare'])->where('token', '[A-Za-z0-9]{64}')->middleware('throttle:60,1')->name('rental.likes.public');
Route::get('/keranjang', [RentalCartController::class, 'index'])->name('cart.index');
Route::post('/keranjang/{product}', [RentalCartController::class, 'store'])->middleware('throttle:30,1')->name('cart.store');
Route::post('/keranjang/unit/{item}/tambah', [RentalCartController::class, 'duplicate'])->middleware('throttle:30,1')->name('cart.duplicate');
Route::delete('/keranjang/{item}', [RentalCartController::class, 'destroy'])->name('cart.destroy');

Route::middleware('guest')->group(function () {

    // Alias pendek berbahasa Indonesia. Pengguna sering mengetik /masuk atau
    // /daftar; tanpa rute ini keduanya berakhir 404 padahal halamannya ada.
    Route::redirect('/masuk', '/login')->name('login.alias');
    Route::redirect('/daftar', '/register')->name('register.alias');

    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])
        ->middleware('throttle:5,1')
        ->name('login.post');

    // Login dengan Google (sign-in only — akun harus sudah terdaftar)
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');

    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('register.post');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendLink'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('password.update.reset');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Email verification (opsional — hanya untuk user yang mengisi email)
    Route::view('/verify-email', 'auth.verify-email')->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');

    // Alamat pendek yang sering diketik atau dibuka dari penanda lama. Tanpa
    // rute GET, /profile jatuh ke PATCH/DELETE (perbarui & hapus akun) dan
    // berakhir 405 Method Not Allowed. Arahkan ke halaman profil sesuai peran.
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::post('/profile/primary-address', [ProfileController::class, 'savePrimaryAddress'])
        ->middleware('role:customer')
        ->name('profile.primary-address');

    Route::get('/pembayaran-sewa/qris', [RentalPaymentSettingController::class, 'qris'])->name('rental.payment.qris');
    Route::get('/ringkasan-sewa/{order}', [RentalReceiptController::class, 'show'])->name('rental.receipt');
    Route::get('/pembayaran-sewa/{payment}/bukti', [RentalPaymentController::class, 'proof'])->name('rental.payment.proof');
    Route::get('/preview-desain/{preview}/gambar', [RentalDesignController::class, 'image'])->name('rental.design.image');
    Route::get('/referensi-pesanan/{reference}/gambar', [RentalOrderController::class, 'referenceImage'])->name('rental.reference.image');

    Route::middleware('role:customer')->group(function () {
        Route::post('/katalog/{product}/suka', [RentalFeedbackController::class, 'productLike'])->middleware('throttle:30,1')->name('rental.product.like');
        Route::post('/sewa/{order}/unit/{unit}/rating', [RentalFeedbackController::class, 'productRating'])->middleware('throttle:10,1')->name('rental.product.rating');
        Route::post('/sewa/{order}/rating', [RentalFeedbackController::class, 'orderRating'])->middleware('throttle:10,1')->name('rental.order.rating');
        Route::post('/sewa/{order}/bagikan-suka', [RentalFeedbackController::class, 'share'])->middleware('throttle:10,1')->name('rental.order.share');
        Route::post('/apresiasi/{token}/suka', [RentalFeedbackController::class, 'orderLike'])->where('token', '[A-Za-z0-9]{64}')->middleware('throttle:30,1')->name('rental.order.like');
        Route::post('/sewa/unit/{unit}/setujui-ongkos', [RentalOrderController::class, 'approveTransport'])->name('rental.transport.approve');
        Route::post('/sewa/unit/{unit}/tolak-ongkos', [RentalOrderController::class, 'rejectTransport'])->name('rental.transport.reject');
        Route::post('/sewa/unit/{unit}/batal', [RentalOrderController::class, 'cancelByCustomer'])->name('rental.cancel.customer');
        Route::post('/sewa/unit/{unit}/ubah-data', [RentalOrderController::class, 'updateUnitDetails'])->name('rental.unit.update');
        Route::post('/sewa/unit/{unit}/pesan-ulang', [RentalOrderController::class, 'reorder'])->name('rental.unit.reorder');
        Route::post('/sewa/unit/{unit}/pembayaran', [RentalPaymentController::class, 'store'])->middleware('throttle:10,1')->name('rental.payment.store');
        Route::post('/preview-desain/{preview}/respons', [RentalDesignController::class, 'respond'])->name('rental.design.respond');
        Route::get('/checkout', [RentalOrderController::class, 'create'])->name('rental.checkout');
        Route::post('/checkout', [RentalOrderController::class, 'store'])->middleware('throttle:6,1')->name('rental.checkout.store');
        Route::post('/voucher-sewa/cek', [RentalVoucherController::class, 'check'])->middleware('throttle:20,1')->name('rental.voucher.check');
        Route::get('/sewa', [RentalOrderController::class, 'index'])->name('rental.orders.index');
        Route::get('/sewa/{order}', [RentalOrderController::class, 'show'])->name('rental.orders.show');
        Route::get('/sewa/{order}/unit/{unit}', [RentalOrderController::class, 'customerUnit'])->name('rental.orders.unit');
    });

    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'customer'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'customerShow'])->name('profile');

        Route::get('/notifications', [NotificationController::class, 'customerIndex'])->name('notifications');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/{id}/open', [NotificationController::class, 'markAndRedirect'])->name('notifications.open');

        Route::get('/addresses', [CustomerAddressController::class, 'index'])->name('addresses.index');
        Route::get('/addresses/create', [CustomerAddressController::class, 'create'])->name('addresses.create');
        Route::post('/addresses', [CustomerAddressController::class, 'store'])->name('addresses.store');
        Route::get('/addresses/{address}/edit', [CustomerAddressController::class, 'edit'])->name('addresses.edit');
        Route::put('/addresses/{address}', [CustomerAddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');
        Route::patch('/addresses/{address}/primary', [CustomerAddressController::class, 'setPrimary'])->name('addresses.set-primary');

        Route::get('/help', [HelpController::class, 'customer'])->name('help');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/pembayaran-sewa/{payment}/koreksi', [RentalPaymentController::class, 'correct'])->name('rental.payment.correct');
        Route::post('/pembayaran-sewa/{payment}/pengembalian', [RentalPaymentController::class, 'refund'])->name('rental.payment.refund');
        Route::post('/pembayaran-sewa/{payment}/verifikasi', [RentalPaymentController::class, 'verify'])->name('rental.payment.verify');
        Route::post('/pembayaran-sewa/{payment}/tolak', [RentalPaymentController::class, 'reject'])->name('rental.payment.reject');
        Route::post('/sewa/unit/{unit}/preview', [RentalDesignController::class, 'store'])->name('rental.design.store');
        Route::post('/sewa/unit/{unit}/desain/lanjutkan', [RentalDesignController::class, 'override'])->name('rental.design.override');
        Route::get('/jadwal-sewa', [RentalScheduleController::class, 'index'])->name('rental.schedule');
        Route::get('/jadwal-sewa/lembar-harian', [RentalScheduleController::class, 'dailySheet'])->name('rental.schedule.daily-sheet');
        Route::get('/sewa', [RentalOrderController::class, 'adminIndex'])->name('rental.orders');
        Route::get('/sewa/{order}', [RentalOrderController::class, 'adminShow'])->name('rental.orders.show');
        Route::post('/sewa/unit/{unit}/selesai-tinjau-pembayaran', [RentalPaymentController::class, 'resolveReview'])->name('rental.payment.resolve-review');
        Route::post('/sewa/unit/{unit}/perkembangan', [RentalProgressController::class, 'advance'])->name('rental.progress.advance');
        Route::post('/sewa/unit/{unit}/ongkos', [RentalOrderController::class, 'quoteTransport'])->name('rental.transport.quote');
        Route::post('/sewa/unit/{unit}/confirm', [RentalOrderController::class, 'confirm'])->name('rental.confirm');
        Route::post('/sewa/unit/{unit}/lanjutkan', [RentalOrderController::class, 'resume'])->name('rental.resume');
        Route::post('/sewa/unit/{unit}/cancel', [RentalOrderController::class, 'cancel'])->name('rental.cancel');
        Route::get('/universitas', [RentalUniversityController::class, 'index'])->name('rental.universities.index');
        Route::post('/universitas', [RentalUniversityController::class, 'store'])->name('rental.universities.store');
        Route::put('/universitas/{university}', [RentalUniversityController::class, 'update'])->name('rental.universities.update');
        Route::patch('/universitas/{university}/status', [RentalUniversityController::class, 'toggle'])->name('rental.universities.toggle');
        Route::get('/item-tambahan', [RentalAddonController::class, 'index'])->name('rental.addons.index');
        Route::post('/item-tambahan', [RentalAddonController::class, 'store'])->name('rental.addons.store');
        Route::put('/item-tambahan/{addon}', [RentalAddonController::class, 'update'])->name('rental.addons.update');
        Route::patch('/item-tambahan/{addon}/status', [RentalAddonController::class, 'toggle'])->name('rental.addons.toggle');
        Route::delete('/item-tambahan/{addon}', [RentalAddonController::class, 'destroy'])->name('rental.addons.destroy');
        Route::get('/voucher-sewa', [RentalVoucherController::class, 'index'])->name('rental.vouchers.index');
        Route::post('/voucher-sewa', [RentalVoucherController::class, 'store'])->name('rental.vouchers.store');
        Route::patch('/voucher-sewa/{voucher}/status', [RentalVoucherController::class, 'toggle'])->name('rental.vouchers.toggle');
        Route::get('/katalog', [RentalCatalogController::class, 'admin'])->name('catalog.index');
        Route::post('/katalog/kategori', [RentalCatalogController::class, 'categoryStore'])->name('catalog.categories.store');
        Route::put('/katalog/kategori/{category}', [RentalCatalogController::class, 'categoryUpdate'])->name('catalog.categories.update');
        Route::patch('/katalog/kategori/{category}/status', [RentalCatalogController::class, 'categoryToggle'])->name('catalog.categories.toggle');
        Route::post('/katalog/{product}/paket', [RentalCatalogController::class, 'packageStore'])->name('catalog.packages.store');
        Route::put('/katalog/paket/{package}', [RentalCatalogController::class, 'packageUpdate'])->name('catalog.packages.update');
        Route::patch('/katalog/paket/{package}/status', [RentalCatalogController::class, 'packageToggle'])->name('catalog.packages.toggle');
        Route::post('/katalog', [RentalCatalogController::class, 'productStore'])->name('catalog.store');
        Route::put('/katalog/{product}', [RentalCatalogController::class, 'productUpdate'])->name('catalog.update');
        Route::patch('/katalog/{product}/toggle', [RentalCatalogController::class, 'toggle'])->name('catalog.toggle');
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        Route::get('/pengaturan-pembayaran-sewa', [RentalPaymentSettingController::class, 'edit'])->name('rental.payment-settings.edit');
        Route::put('/pengaturan-pembayaran-sewa', [RentalPaymentSettingController::class, 'update'])->name('rental.payment-settings.update');
        Route::get('/buku-kas', [RentalCashBookController::class, 'index'])->name('rental.cashbook.index');
        Route::get('/buku-kas/ekspor', [RentalCashBookController::class, 'export'])->name('rental.cashbook.export');
        Route::post('/buku-kas', [RentalCashBookController::class, 'store'])->name('rental.cashbook.store');
        Route::put('/buku-kas/{entry}', [RentalCashBookController::class, 'update'])->name('rental.cashbook.update');
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

        Route::get('/notifications', [NotificationController::class, 'adminIndex'])->name('notifications');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/{id}/open', [NotificationController::class, 'markAndRedirect'])->name('notifications.open');

        Route::get('/profile', [ProfileController::class, 'adminShow'])->name('profile');

        Route::get('/help', [HelpController::class, 'admin'])->name('help');
    });

    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->middleware('role:admin')->name('dashboard.admin');
    Route::get('/dashboard/customer', [DashboardController::class, 'customer'])->middleware('role:customer')->name('dashboard.customer');
});
