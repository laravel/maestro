<?php

namespace App\Support;

use Laravel\Fortify\Features;

class FortifyFeaturePayload
{
    public static function registerUrl(): ?string
    {
        return Features::enabled(Features::registration()) ? route('register') : null;
    }

    public static function forgotPasswordUrl(): ?string
    {
        return Features::enabled(Features::resetPasswords()) ? route('password.request') : null;
    }

    public static function forgotPasswordSubmitUrl(): ?string
    {
        return Features::enabled(Features::resetPasswords()) ? route('password.email') : null;
    }

    public static function resetPasswordSubmitUrl(): ?string
    {
        return Features::enabled(Features::resetPasswords()) ? route('password.update') : null;
    }

    public static function verificationSendUrl(): ?string
    {
        return Features::enabled(Features::emailVerification()) ? route('verification.send') : null;
    }

    public static function twoFactorLoginUrl(): ?string
    {
        return Features::enabled(Features::twoFactorAuthentication()) ? route('two-factor.login.store') : null;
    }

    /**
     * @return array{enableUrl: string, disableUrl: string, confirmUrl: string, qrCodeUrl: string, secretKeyUrl: string, recoveryCodesUrl: string, regenerateUrl: string}|null
     */
    public static function twoFactorUrls(): ?array
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            return null;
        }

        return [
            'enableUrl' => route('two-factor.enable'),
            'disableUrl' => route('two-factor.disable'),
            'confirmUrl' => route('two-factor.confirm'),
            'qrCodeUrl' => route('two-factor.qr-code'),
            'secretKeyUrl' => route('two-factor.secret-key'),
            'recoveryCodesUrl' => route('two-factor.recovery-codes'),
            'regenerateUrl' => route('two-factor.regenerate-recovery-codes'),
        ];
    }
}
