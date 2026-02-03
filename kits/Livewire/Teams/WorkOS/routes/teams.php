<?php

use App\Http\Middleware\EnsureMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('teams', 'pages::teams.index')->name('teams.index');

    Route::middleware(EnsureMembership::class)->group(function () {
        Route::livewire('teams/{team}', 'pages::teams.edit')->name('teams.edit');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});
