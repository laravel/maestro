<?php

use App\Support\FortifyFeaturePayload;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', '{{welcome}}', [
    'canRegister' => fn () => Features::enabled(Features::registration()),
    'registerUrl' => fn () => FortifyFeaturePayload::registerUrl(),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', '{{dashboard}}')->name('dashboard');
});

require __DIR__.'/settings.php';
