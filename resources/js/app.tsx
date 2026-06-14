import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Toaster } from 'react-hot-toast';

const appName = (import.meta as any).env?.VITE_APP_NAME || 'IKR ISP';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.tsx`,
        (import.meta as any).glob('./Pages/**/*.tsx'),
    ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<>
            <App {...props} />
            <Toaster position="top-right" />
        </>);
    },
    progress: {
        color: '#2563eb',
    },
});
