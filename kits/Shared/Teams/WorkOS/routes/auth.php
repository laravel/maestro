<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;

Route::get('login', function (AuthKitLoginRequest $request) {
    return $request->redirect();
})->middleware(['guest'])->name('login');

Route::get('authenticate', function (AuthKitAuthenticationRequest $request) {
    $request->authenticate();

    $user = auth()->user();
    $currentTeam = $user->currentTeam ?? $user->personalTeam();

    if ($currentTeam && ! $user->current_team_id) {
        $user->switchTeam($currentTeam);
    }

    if ($currentTeam) {
        URL::defaults(['current_team' => $currentTeam->slug]);
    }

    return redirect()->intended(route('dashboard'));
})->middleware(['guest']);

Route::post('logout', function (AuthKitLogoutRequest $request) {
    return $request->logout();
})->middleware(['auth'])->name('logout');
