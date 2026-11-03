<?php

namespace App\Livewire\Settings\TwoFactor;

/* @chisel-password-confirmation */
use App\Livewire\Actions\EnsurePasswordIsConfirmed;
/* @end-chisel-password-confirmation */
use Exception;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RecoveryCodes extends Component
{
    /** @var list<string> */
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Load the recovery codes so the user can view them.
     */
    public function viewRecoveryCodes(): void
    {
        /* @chisel-password-confirmation */
        app(EnsurePasswordIsConfirmed::class)();
        /* @end-chisel-password-confirmation */

        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        /* @chisel-password-confirmation */
        app(EnsurePasswordIsConfirmed::class)();
        /* @end-chisel-password-confirmation */

        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}
