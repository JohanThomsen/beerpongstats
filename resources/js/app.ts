import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { configureEcho } from '@laravel/echo-vue';
import Pusher from 'pusher-js';

// Reverb uses the Pusher protocol; make it available to Echo
// eslint-disable-next-line @typescript-eslint/no-explicit-any
(window as any).Pusher = Pusher;

// Get Reverb configuration from environment variables
const reverbHost = import.meta.env.VITE_REVERB_HOST || 'localhost';
const reverbPort = import.meta.env.VITE_REVERB_PORT || '8080';
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'http';
const reverbAppKey = import.meta.env.VITE_REVERB_APP_KEY || '';

// Determine if we should use secure connections
const isSecure = reverbScheme === 'https';

// For production HTTPS, don't specify a port (use default 443)
// For development, use the specified port
const wsPort = isSecure && reverbPort === '443' ? undefined : parseInt(reverbPort);
const wssPort = isSecure && reverbPort === '443' ? undefined : parseInt(reverbPort);

configureEcho({
    broadcaster: 'reverb',
    key: reverbAppKey,
    wsHost: reverbHost,
    wsPort: wsPort,
    wssPort: wssPort,
    forceTLS: isSecure,
    encrypted: isSecure,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
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
