import { useLayoutProps } from '@inertiajs/react';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({ children }: { children: React.ReactNode }) {
    const { breadcrumbs } = useLayoutProps({
        breadcrumbs: [] as BreadcrumbItem[],
    });

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            {children}
        </AppLayoutTemplate>
    );
}
