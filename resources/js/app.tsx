import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
});

if (typeof window !== 'undefined') {
    initializeTheme();
}
