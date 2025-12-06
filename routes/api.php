<?php

use App\Http\Controllers\Api\LicenseValidationController;
use Illuminate\Support\Facades\Route;

Route::post('/licenses/validate', LicenseValidationController::class)
	->name('api.licenses.validate');
