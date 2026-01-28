<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('{{welcome}}', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('{current_team}/dashboard', function () {
    return Inertia::render('{{dashboard}}');
})->middleware(['auth', 'verified', \App\Http\Middleware\EnsureMembership::class])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/teams.php';
