import { useLayoutProps } from '@inertiajs/react';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

export default function AuthLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { title, description } = useLayoutProps({
        title: '',
        description: '',
    });

    return (
        <AuthLayoutTemplate title={title} description={description}>
            {children}
        </AuthLayoutTemplate>
    );
}
