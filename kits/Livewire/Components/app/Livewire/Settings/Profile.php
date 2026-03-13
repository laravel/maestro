<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
/* @email-verification */
use Illuminate\Contracts\Auth\MustVerifyEmail;
/* @end-email-verification */
use Illuminate\Support\Facades\Auth;
/* @email-verification */
use Illuminate\Support\Facades\Session;
/* @end-email-verification */
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profile settings')]
class Profile extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        /* @email-verification */
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
        /* @end-email-verification */
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $hasUnverifiedEmail = false;

        /* @email-verification */
        $hasUnverifiedEmail = Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
        /* @end-email-verification */

        return $hasUnverifiedEmail;
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        $showDeleteUser = true;

        /* @email-verification */
        $showDeleteUser = ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
        /* @end-email-verification */

        return $showDeleteUser;
    }
}
