import type { LinkComponentBaseProps } from '@inertiajs/core';
import { page } from '@inertiajs/svelte';
import type { Readable } from 'svelte/store';
import { derived } from 'svelte/store';
import { toUrl } from '@/lib/utils';

export type CurrentUrlState = {
    currentUrl: Readable<string>;
    isCurrentUrl: (
        urlToCheck: NonNullable<LinkComponentBaseProps['href']>,
        currentUrl: string,
    ) => boolean;
    whenCurrentUrl: <TIfTrue, TIfFalse = null>(
        urlToCheck: NonNullable<LinkComponentBaseProps['href']>,
        currentUrl: string,
        ifTrue: TIfTrue,
        ifFalse?: TIfFalse,
    ) => TIfTrue | TIfFalse;
};

const currentUrl = derived(page, ($page) => {
    const origin =
        typeof window === 'undefined'
            ? 'http://localhost'
            : window.location.origin;

    try {
        return new URL($page.url, origin).pathname;
    } catch {
        return $page.url;
    }
});

export function currentUrlState(): CurrentUrlState {
    function isCurrentUrl(
        urlToCheck: NonNullable<LinkComponentBaseProps['href']>,
        current: string,
    ): boolean {
        const urlString = toUrl(urlToCheck);

        if (!urlString.startsWith('http')) {
            return urlString === current;
        }

        try {
            const absoluteUrl = new URL(urlString);
            return absoluteUrl.pathname === current;
        } catch {
            return false;
        }
    }

    function whenCurrentUrl<TIfTrue, TIfFalse = null>(
        urlToCheck: NonNullable<LinkComponentBaseProps['href']>,
        current: string,
        ifTrue: TIfTrue,
        ifFalse: TIfFalse = null as TIfFalse,
    ): TIfTrue | TIfFalse {
        return isCurrentUrl(urlToCheck, current) ? ifTrue : ifFalse;
    }

    return {
        currentUrl,
        isCurrentUrl,
        whenCurrentUrl,
    };
}
