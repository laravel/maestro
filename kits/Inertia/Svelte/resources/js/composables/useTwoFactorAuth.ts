import type { Readable } from 'svelte/store';
import { derived, writable } from 'svelte/store';
import { qrCode, recoveryCodes, secretKey } from '@/routes/two-factor';

export type UseTwoFactorAuthReturn = {
    qrCodeSvg: Readable<string | null>;
    manualSetupKey: Readable<string | null>;
    recoveryCodesList: Readable<string[]>;
    errors: Readable<string[]>;
    hasSetupData: Readable<boolean>;
    clearSetupData: () => void;
    clearErrors: () => void;
    clearTwoFactorAuthData: () => void;
    fetchQrCode: () => Promise<void>;
    fetchSetupKey: () => Promise<void>;
    fetchSetupData: () => Promise<void>;
    fetchRecoveryCodes: () => Promise<void>;
};

const fetchJson = async <T>(url: string): Promise<T> => {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch: ${response.status}`);
    }

    return response.json();
};

const qrCodeSvg = writable<string | null>(null);
const manualSetupKey = writable<string | null>(null);
const recoveryCodesList = writable<string[]>([]);
const errors = writable<string[]>([]);

const hasSetupData = derived(
    [qrCodeSvg, manualSetupKey],
    ([$qr, $key]) => $qr !== null && $key !== null,
);

export function useTwoFactorAuth(): UseTwoFactorAuthReturn {
    const fetchQrCode = async (): Promise<void> => {
        try {
            const { svg } = await fetchJson<{ svg: string; url: string }>(
                qrCode.url(),
            );

            qrCodeSvg.set(svg);
        } catch {
            errors.update((items) => [...items, 'Failed to fetch QR code']);
            qrCodeSvg.set(null);
        }
    };

    const fetchSetupKey = async (): Promise<void> => {
        try {
            const { secretKey: key } = await fetchJson<{ secretKey: string }>(
                secretKey.url(),
            );

            manualSetupKey.set(key);
        } catch {
            errors.update((items) => [...items, 'Failed to fetch a setup key']);
            manualSetupKey.set(null);
        }
    };

    const clearErrors = (): void => {
        errors.set([]);
    };

    const clearSetupData = (): void => {
        manualSetupKey.set(null);
        qrCodeSvg.set(null);
        clearErrors();
    };

    const clearTwoFactorAuthData = (): void => {
        clearSetupData();
        recoveryCodesList.set([]);
        clearErrors();
    };

    const fetchRecoveryCodes = async (): Promise<void> => {
        try {
            clearErrors();
            recoveryCodesList.set(
                await fetchJson<string[]>(recoveryCodes.url()),
            );
        } catch {
            errors.update((items) => [...items, 'Failed to fetch recovery codes']);
            recoveryCodesList.set([]);
        }
    };

    const fetchSetupData = async (): Promise<void> => {
        try {
            clearErrors();
            await Promise.all([fetchQrCode(), fetchSetupKey()]);
        } catch {
            qrCodeSvg.set(null);
            manualSetupKey.set(null);
        }
    };

    return {
        qrCodeSvg,
        manualSetupKey,
        recoveryCodesList,
        errors,
        hasSetupData,
        clearSetupData,
        clearErrors,
        clearTwoFactorAuthData,
        fetchQrCode,
        fetchSetupKey,
        fetchSetupData,
        fetchRecoveryCodes,
    };
}
