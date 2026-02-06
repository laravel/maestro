import type { LinkComponentBaseProps } from '@inertiajs/core';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<LinkComponentBaseProps['href']>): string {
    return typeof href === 'string' ? href : href.url;
}
