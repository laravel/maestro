import { useHttp } from '@inertiajs/vue3';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';

export type TwoFactorAuthUrls = {
    qrCodeUrl: string;
    secretKeyUrl: string;
    recoveryCodesUrl: string;
};

export type UseTwoFactorAuthReturn = {
    qrCodeSvg: Ref<string | null>;
    manualSetupKey: Ref<string | null>;
    recoveryCodesList: Ref<string[]>;
    errors: Ref<string[]>;
    hasSetupData: ComputedRef<boolean>;
    clearSetupData: () => void;
    clearErrors: () => void;
    clearTwoFactorAuthData: () => void;
    fetchQrCode: () => Promise<void>;
    fetchSetupKey: () => Promise<void>;
    fetchSetupData: () => Promise<void>;
    fetchRecoveryCodes: () => Promise<void>;
};

const errors = ref<string[]>([]);
const manualSetupKey = ref<string | null>(null);
const qrCodeSvg = ref<string | null>(null);
const recoveryCodesList = ref<string[]>([]);
const cachedUrls = ref<TwoFactorAuthUrls | null>(null);

const hasSetupData = computed<boolean>(
    () => qrCodeSvg.value !== null && manualSetupKey.value !== null,
);

export const useTwoFactorAuth = (
    urls?: TwoFactorAuthUrls,
): UseTwoFactorAuthReturn => {
    const http = useHttp();

    if (urls) {
        cachedUrls.value = urls;
    }

    const fetchQrCode = async (): Promise<void> => {
        if (!cachedUrls.value) {
            return;
        }

        try {
            const { svg } = (await http.submit({
                url: cachedUrls.value.qrCodeUrl,
                method: 'get',
            })) as {
                svg: string;
                url: string;
            };

            qrCodeSvg.value = svg;
        } catch {
            errors.value.push('Failed to fetch QR code');
            qrCodeSvg.value = null;
        }
    };

    const fetchSetupKey = async (): Promise<void> => {
        if (!cachedUrls.value) {
            return;
        }

        try {
            const { secretKey: key } = (await http.submit({
                url: cachedUrls.value.secretKeyUrl,
                method: 'get',
            })) as {
                secretKey: string;
            };

            manualSetupKey.value = key;
        } catch {
            errors.value.push('Failed to fetch a setup key');
            manualSetupKey.value = null;
        }
    };

    const clearSetupData = (): void => {
        manualSetupKey.value = null;
        qrCodeSvg.value = null;
        clearErrors();
    };

    const clearErrors = (): void => {
        errors.value = [];
    };

    const clearTwoFactorAuthData = (): void => {
        clearSetupData();
        clearErrors();
        recoveryCodesList.value = [];
    };

    const fetchRecoveryCodes = async (): Promise<void> => {
        if (!cachedUrls.value) {
            return;
        }

        try {
            clearErrors();
            recoveryCodesList.value = (await http.submit({
                url: cachedUrls.value.recoveryCodesUrl,
                method: 'get',
            })) as string[];
        } catch {
            errors.value.push('Failed to fetch recovery codes');
            recoveryCodesList.value = [];
        }
    };

    const fetchSetupData = async (): Promise<void> => {
        try {
            clearErrors();
            await Promise.all([fetchQrCode(), fetchSetupKey()]);
        } catch {
            qrCodeSvg.value = null;
            manualSetupKey.value = null;
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
};
