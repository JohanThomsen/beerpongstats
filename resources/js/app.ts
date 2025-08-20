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
