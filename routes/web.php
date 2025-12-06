<?php

use App\Http\Controllers\Admin\LicenseController as AdminLicenseController;
use App\Http\Controllers\Admin\LicenseValidationTestController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserLicenseController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/api-lab', 'api.lab')->name('api.lab');

Route::get('/shop', ShopController::class)->name('shop');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

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

Route::get('/dashboard/licenses/{license}', [UserLicenseController::class, 'show'])
    ->middleware('auth')
    ->name('licenses.show');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::redirect('/', '/admin/licenses')->name('home');
        Route::resource('licenses', AdminLicenseController::class)->except(['show']);
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::get('tools/license-validation', LicenseValidationTestController::class)->name('tools.license-validation');
    });
