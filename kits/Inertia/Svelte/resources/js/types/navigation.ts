import type { LinkComponentBaseProps } from '@inertiajs/core';
import type { Component } from 'svelte';

export type BreadcrumbItem = {
    title: string;
    href?: string;
};

export type NavItem = {
    title: string;
    href: NonNullable<LinkComponentBaseProps['href']>;
    icon?: Component<{ class?: string }>;
    isActive?: boolean;
};
