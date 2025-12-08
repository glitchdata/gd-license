<?php

use App\Http\Controllers\LicenseValidatorJsonController;
use App\Http\Controllers\Admin\LicenseController as AdminLicenseController;
use App\Http\Controllers\Admin\LicenseValidationTestController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\EventLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTestController;
use App\Http\Controllers\PayPalOrderController;
use App\Http\Controllers\PublicLicenseValidatorController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserLicenseController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/api-lab', 'api.lab')->name('api.lab');
Route::get('/license/{license_code}', PublicLicenseValidatorController::class)
    ->name('licenses.validator');
Route::get('/license/validate/{key}', LicenseValidatorJsonController::class);

Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/shop/{product:product_code}', [ShopController::class, 'show'])->name('shop.products.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/login/two-factor', [LoginController::class, 'showTwoFactorForm'])->name('login.two-factor.show');
    Route::post('/login/two-factor', [LoginController::class, 'verifyTwoFactor'])->name('login.two-factor.verify');
    Route::post('/login/two-factor/resend', [LoginController::class, 'resendTwoFactor'])->name('login.two-factor.resend');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::post('/dashboard/licenses', [UserLicenseController::class, 'store'])
    ->middleware('auth')
    ->name('licenses.store');

Route::post('/paypal/orders', [PayPalOrderController::class, 'store'])
    ->middleware('auth')
    ->name('paypal.orders.store');

Route::get('/dashboard/licenses/{license}', [UserLicenseController::class, 'show'])
    ->middleware('auth')
    ->name('licenses.show');

Route::middleware('auth')->group(function () {
    Route::get('/email-test', [EmailTestController::class, 'create'])->name('email.test');
    Route::post('/email-test', [EmailTestController::class, 'store'])->name('email.test.send');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::redirect('/', '/admin/licenses')->name('home');
        Route::resource('licenses', AdminLicenseController::class)->except(['show']);
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::get('tools/license-validation', LicenseValidationTestController::class)->name('tools.license-validation');
        Route::get('logs', [AdminLogController::class, 'index'])->name('logs.index');
        Route::get('event-logs', [EventLogController::class, 'index'])->name('event-logs.index');
    });
