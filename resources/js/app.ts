import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { configureEcho, echo } from '@laravel/echo-vue';
import Pusher from 'pusher-js';

// Reverb uses the Pusher protocol; make it available to Echo
// eslint-disable-next-line @typescript-eslint/no-explicit-any
(window as any).Pusher = Pusher;

configureEcho({
    broadcaster: 'reverb',
});

// Simple POC listener for testing via `php artisan ws:ping`
if (typeof window !== 'undefined') {
    // Provide a small delay to ensure Echo is initialized in all environments
    setTimeout(() => {
        try {
            // Log connection state changes (best-effort; pusher internals)
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const conn: any = (echo() as any)?.connector?.pusher?.connection;
            conn?.bind?.('connected', () => console.info('[ws] connected'));
            conn?.bind?.('error', (e: unknown) => console.warn('[ws] error', e));

            console.info('[ws] listening on channel: poc, event: .PocPing');
            echo()
                .channel('poc')
                .listen('.PocPing', (e: unknown) => {
                    console.info('[ws] PocPing received', e);
                    window.dispatchEvent(new CustomEvent('poc:ping', { detail: e }));
                });
        } catch (e) {
            console.warn('[ws] echo init failed', e);
        }
    }, 0);
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const ziggy = (window as any).Ziggy ?? undefined;
        createApp({ render: () => h(App, props) })
            .use(plugin)
            // Provide Ziggy with the server routes when available
            .use(ZiggyVue, ziggy)
            .mount(el);
    },
    progress: { color: '#4B5563' },
});

// This will set light / dark mode on page load...
initializeTheme();
