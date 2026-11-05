import { createInertiaApp } from '@inertiajs/vue3';
import { defineAsyncComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const loadAppLayout = () => import('@/layouts/AppLayout.vue');
const loadSettingsLayout = () => import('@/layouts/settings/Layout.vue');

const AppLayout = defineAsyncComponent(loadAppLayout);
const SettingsLayout = defineAsyncComponent(loadSettingsLayout);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// This will preload the layouts once the browser is idle...
if (typeof window !== 'undefined') {
    const preloadLayouts = () => {
        void loadAppLayout();
        void loadSettingsLayout();
    };

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(preloadLayouts);
    } else {
        setTimeout(preloadLayouts, 1);
    }
}
