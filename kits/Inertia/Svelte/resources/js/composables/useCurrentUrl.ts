import type { LinkComponentBaseProps } from '@inertiajs/core';
import type { Readable } from 'svelte/store';
import { toUrl } from '@/lib/utils';
import { page } from '@inertiajs/svelte';
import { derived, get } from 'svelte/store';

export type UseCurrentUrlReturn = {
    currentUrl: Readable<string>;
    isCurrentUrl: (urlToCheck: NonNullable<LinkComponentBaseProps['href']>, currentUrl?: string) => boolean;
    whenCurrentUrl: <TIfTrue, TIfFalse = null>(
        urlToCheck: NonNullable<LinkComponentBaseProps['href']>,
        ifTrue: TIfTrue,
        ifFalse?: TIfFalse,
    ) => TIfTrue | TIfFalse;
};

const currentUrl = derived(page, ($page) => {
    const origin = typeof window === 'undefined' ? 'http://localhost' : window.location.origin;

    try {
        return new URL($page.url, origin).pathname;
    } catch {
        return $page.url;
    }
});

export function useCurrentUrl(): UseCurrentUrlReturn {
    function isCurrentUrl(urlToCheck: NonNullable<LinkComponentBaseProps['href']>, currentOverride?: string): boolean {
        const urlToCompare = currentOverride ?? get(currentUrl);
        const urlString = toUrl(urlToCheck);

        if (!urlString.startsWith('http')) {
            return urlString === urlToCompare;
        }

        try {
            const absoluteUrl = new URL(urlString);
            return absoluteUrl.pathname === urlToCompare;
        } catch {
            return false;
        }
    }

    function whenCurrentUrl<TIfTrue, TIfFalse = null>(
        urlToCheck: NonNullable<LinkComponentBaseProps['href']>,
        ifTrue: TIfTrue,
        ifFalse: TIfFalse = null as TIfFalse,
    ): TIfTrue | TIfFalse {
        return isCurrentUrl(urlToCheck) ? ifTrue : ifFalse;
    }

    return {
        currentUrl,
        isCurrentUrl,
        whenCurrentUrl,
    };
}
