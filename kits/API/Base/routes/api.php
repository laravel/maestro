<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SendVerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::post('register', RegisterController::class)->name('register');
Route::post('login', LoginController::class)->middleware('throttle:5,1')->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', LogoutController::class)->name('logout');
    Route::post('token/refresh', RefreshTokenController::class)->name('token.refresh');

    Route::get('me', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('user', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('verified')->group(function () {
        Route::delete('user', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::patch('user/password', PasswordUpdateController::class)->name('password.update');
    });

    Route::post('email/verification-notification', SendVerificationNotificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware('signed')
        ->name('verification.verify');
});
