import { Head, router } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import Heading from '@/components/heading';
import PasskeyItem from '@/components/passkey-item';
import PasskeyRegistration from '@/components/passkey-register';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index } from '@/routes/passkeys';
import type { BreadcrumbItem } from '@/types';

type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

type Props = {
    passkeys: Passkey[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Passkeys',
        href: index(),
    },
];

function EmptyState() {
    return (
        <div className="p-8 text-center">
            <div className="bg-muted mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl">
                <KeyRound className="text-muted-foreground h-7 w-7" />
            </div>
            <p className="font-medium">No passkeys yet</p>
            <p className="text-muted-foreground mt-1 text-sm">
                Add a passkey to sign in without a password
            </p>
        </div>
    );
}

export default function Passkeys({ passkeys }: Props) {
    const handleDelete = (id: number) => {
        router.delete(destroy.url(id), {
            preserveScroll: true,
        });
    };

    const handleRegisterSuccess = () => {
        router.reload();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Passkeys" />

            <h1 className="sr-only">Passkey Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Passkeys"
                        description="Manage your passkeys for passwordless sign-in"
                    />

                    <div className="border-border overflow-hidden rounded-lg border">
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

                    <PasskeyRegistration onSuccess={handleRegisterSuccess} />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
