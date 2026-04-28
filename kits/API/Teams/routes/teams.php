<?php

use App\Http\Controllers\Teams\AcceptTeamInvitationController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::get('invitations/{invitation}/accept', AcceptTeamInvitationController::class)
    ->middleware('signed')
    ->name('invitations.accept');

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('verified')->group(function () {
        Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
        Route::post('teams', [TeamController::class, 'store'])->name('teams.store');

        Route::middleware(EnsureTeamMembership::class)->group(function () {
            Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');
            Route::patch('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
            Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');

            Route::patch('teams/{team}/members/{user}', [TeamMemberController::class, 'update'])
                ->name('teams.members.update');
            Route::delete('teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])
                ->name('teams.members.destroy');

            Route::post('teams/{team}/invitations', [TeamInvitationController::class, 'store'])
                ->name('teams.invitations.store');
            Route::delete('teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])
                ->name('teams.invitations.destroy');
        });
    });
});
