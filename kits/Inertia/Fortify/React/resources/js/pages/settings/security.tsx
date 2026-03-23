import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
/* @passkeys */
import { router } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
/* @end-passkeys */
/* @2fa */
import { ShieldCheck } from 'lucide-react';
import { useState } from 'react';
/* @end-2fa */
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
/* @passkeys */
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
/* @end-passkeys */
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
/* @passkeys */
import PasskeyItem from '@/components/passkey-item';
import PasskeyRegistration from '@/components/passkey-register';
/* @end-passkeys */
import PasswordInput from '@/components/password-input';
/* @2fa */
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
/* @end-2fa */
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
/* @2fa */
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
/* @end-2fa */
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/security';
/* @2fa */
import { disable, enable } from '@/routes/two-factor';
/* @end-2fa */
import type { BreadcrumbItem } from '@/types';
/* @passkeys */
import type { Passkey } from '@/types/auth';
/* @end-passkeys */

type Props = Record<string, never> & {
    /* @2fa */
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    /* @end-2fa */
    /* @passkeys */
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
    /* @end-passkeys */
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Security settings',
        href: edit(),
    },
];

/* @passkeys */
function EmptyState() {
    return (
        <div className="p-8 text-center">
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                <KeyRound className="h-7 w-7 text-muted-foreground" />
            </div>
            <p className="font-medium">No passkeys yet</p>
            <p className="mt-1 text-sm text-muted-foreground">
                Add a passkey to sign in without a password
            </p>
        </div>
    );
}
/* @end-passkeys */

export default function Security(props: Props) {
    void props;

    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    /* @2fa */
    const canManageTwoFactor = props.canManageTwoFactor ?? false;
    const requiresConfirmation = props.requiresConfirmation ?? false;
    const twoFactorEnabled = props.twoFactorEnabled ?? false;

    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);
    /* @end-2fa */

    /* @passkeys */
    const canManagePasskeys = props.canManagePasskeys ?? false;
    const passkeys = props.passkeys ?? [];

    const handleDelete = (id: number) => {
        router.delete(destroy.url(id), {
            preserveScroll: true,
        });
    };

    const handleRegisterSuccess = () => {
        router.reload();
    };
    /* @end-passkeys */

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Security settings" />

            <h1 className="sr-only">Security settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Update password"
                        description="Ensure your account is using a long, random password to stay secure"
                    />

                    <Form
                        {...SecurityController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        resetOnError={[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]}
                        resetOnSuccess
                        onError={(errors) => {
                            if (errors.password) {
                                passwordInput.current?.focus();
                            }

                            if (errors.current_password) {
                                currentPasswordInput.current?.focus();
                            }
                        }}
                        className="space-y-6"
                    >
                        {({ errors, processing, recentlySuccessful }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="current_password">
                                        Current password
                                    </Label>

                                    <PasswordInput
                                        id="current_password"
                                        ref={currentPasswordInput}
                                        name="current_password"
                                        className="mt-1 block w-full"
                                        autoComplete="current-password"
                                        placeholder="Current password"
                                    />

                                    <InputError
                                        message={errors.current_password}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        New password
                                    </Label>

                                    <PasswordInput
                                        id="password"
                                        ref={passwordInput}
                                        name="password"
                                        className="mt-1 block w-full"
                                        autoComplete="new-password"
                                        placeholder="New password"
                                    />

                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirm password
                                    </Label>

                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        className="mt-1 block w-full"
                                        autoComplete="new-password"
                                        placeholder="Confirm password"
                                    />

                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-password-button"
                                    >
                                        Save password
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            Saved
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                {/* @2fa */}
                {canManageTwoFactor && (
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Two-factor authentication"
                            description="Manage your two-factor authentication settings"
                        />
                        {twoFactorEnabled ? (
                            <div className="flex flex-col items-start justify-start space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    You will be prompted for a secure, random
                                    pin during login, which you can retrieve
                                    from the TOTP-supported application on your
                                    phone.
                                </p>

                                <div className="relative inline">
                                    <Form {...disable.form()}>
                                        {({ processing }) => (
                                            <Button
                                                variant="destructive"
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Disable 2FA
                                            </Button>
                                        )}
                                    </Form>
                                </div>

                                <TwoFactorRecoveryCodes
                                    recoveryCodesList={recoveryCodesList}
                                    fetchRecoveryCodes={fetchRecoveryCodes}
                                    errors={errors}
                                />
                            </div>
                        ) : (
                            <div className="flex flex-col items-start justify-start space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    When you enable two-factor authentication,
                                    you will be prompted for a secure pin during
                                    login. This pin can be retrieved from a
                                    TOTP-supported application on your phone.
                                </p>

                                <div>
                                    {hasSetupData ? (
                                        <Button
                                            onClick={() =>
                                                setShowSetupModal(true)
                                            }
                                        >
                                            <ShieldCheck />
                                            Continue setup
                                        </Button>
                                    ) : (
                                        <Form
                                            {...enable.form()}
                                            onSuccess={() =>
                                                setShowSetupModal(true)
                                            }
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    Enable 2FA
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </div>
                            </div>
                        )}

                        <TwoFactorSetupModal
                            isOpen={showSetupModal}
                            onClose={() => setShowSetupModal(false)}
                            requiresConfirmation={requiresConfirmation}
                            twoFactorEnabled={twoFactorEnabled}
                            qrCodeSvg={qrCodeSvg}
                            manualSetupKey={manualSetupKey}
                            clearSetupData={clearSetupData}
                            fetchSetupData={fetchSetupData}
                            errors={errors}
                        />
                    </div>
                )}
                {/* @end-2fa */}

                {/* @passkeys */}
                {canManagePasskeys && (
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Passkeys"
                            description="Manage your passkeys for passwordless sign-in"
                        />

                        <div className="overflow-hidden rounded-lg border border-border">
                            {passkeys.length > 0 ? (
                                passkeys.map((passkey) => (
                                    <PasskeyItem
                                        key={passkey.id}
                                        passkey={passkey}
                                        onDelete={handleDelete}
                                    />
                                ))
                            ) : (
                                <EmptyState />
                            )}
                        </div>

                        <PasskeyRegistration
                            onSuccess={handleRegisterSuccess}
                        />
                    </div>
                )}
                {/* @end-passkeys */}
            </SettingsLayout>
        </AppLayout>
    );
}
