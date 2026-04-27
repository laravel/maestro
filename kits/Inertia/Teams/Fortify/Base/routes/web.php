<?php

use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Support\FortifyFeaturePayload;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', '{{welcome}}', [
    'canRegister' => fn () => Features::enabled(Features::registration()),
    'registerUrl' => fn () => FortifyFeaturePayload::registerUrl(),
])->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::inertia('dashboard', '{{dashboard}}')->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
