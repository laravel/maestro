<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Date;

class EnsurePasswordIsConfirmed
{
    /**
     * Ensure that the user's password has been recently confirmed.
     */
    public function __invoke(): void
    {
        $secondsSinceConfirmation = Date::now()->unix() - (int) session('auth.password_confirmed_at', 0);

        if ($secondsSinceConfirmation > (int) config('auth.password_timeout', 10800)) {
            abort(redirect()->guest(route('password.confirm')));
        }
    }
}
