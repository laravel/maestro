import { useHttp } from '@inertiajs/svelte';

export type TwoFactorAuthUrls = {
    qrCodeUrl: string;
    secretKeyUrl: string;
    recoveryCodesUrl: string;
};

type TwoFactorAuthState = {
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    recoveryCodesList: string[];
    errors: string[];
};

export type TwoFactorAuthStateApi = {
    state: TwoFactorAuthState;
    hasSetupData: () => boolean;
    clearSetupData: () => void;
    clearErrors: () => void;
    clearTwoFactorAuthData: () => void;
    fetchQrCode: () => Promise<void>;
    fetchSetupKey: () => Promise<void>;
    fetchSetupData: () => Promise<void>;
    fetchRecoveryCodes: () => Promise<void>;
};

const state = $state<TwoFactorAuthState>({
    qrCodeSvg: null,
    manualSetupKey: null,
    recoveryCodesList: [],
    errors: [],
});

let cachedUrls: TwoFactorAuthUrls | null = null;

const hasSetupData = (): boolean =>
    state.qrCodeSvg !== null && state.manualSetupKey !== null;

export function twoFactorAuthState(
    urls?: TwoFactorAuthUrls,
): TwoFactorAuthStateApi {
    const http = useHttp();

    if (urls) {
        cachedUrls = urls;
    }

    const fetchQrCode = async (): Promise<void> => {
        if (!cachedUrls) {
            return;
        }

        try {
            const { svg } = (await http.submit({
                url: cachedUrls.qrCodeUrl,
                method: 'get',
            })) as {
                svg: string;
                url: string;
            };

            state.qrCodeSvg = svg;
        } catch {
            state.errors = [...state.errors, 'Failed to fetch QR code'];
            state.qrCodeSvg = null;
        }
    };

    const fetchSetupKey = async (): Promise<void> => {
        if (!cachedUrls) {
            return;
        }

        try {
            const { secretKey: key } = (await http.submit({
                url: cachedUrls.secretKeyUrl,
                method: 'get',
            })) as {
                secretKey: string;
            };

            state.manualSetupKey = key;
        } catch {
            state.errors = [...state.errors, 'Failed to fetch a setup key'];
            state.manualSetupKey = null;
        }
    };

    const clearErrors = (): void => {
        state.errors = [];
    };

    const clearSetupData = (): void => {
        state.manualSetupKey = null;
        state.qrCodeSvg = null;
        clearErrors();
    };

    const clearTwoFactorAuthData = (): void => {
        clearSetupData();
        state.recoveryCodesList = [];
        clearErrors();
    };

    const fetchRecoveryCodes = async (): Promise<void> => {
        if (!cachedUrls) {
            return;
        }

        try {
            clearErrors();
            state.recoveryCodesList = (await http.submit({
                url: cachedUrls.recoveryCodesUrl,
                method: 'get',
            })) as string[];
        } catch {
            state.errors = [...state.errors, 'Failed to fetch recovery codes'];
            state.recoveryCodesList = [];
        }
    };

    const fetchSetupData = async (): Promise<void> => {
        try {
            clearErrors();
            await Promise.all([fetchQrCode(), fetchSetupKey()]);
        } catch {
            state.qrCodeSvg = null;
            state.manualSetupKey = null;
        }
    };

    return {
        state,
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
