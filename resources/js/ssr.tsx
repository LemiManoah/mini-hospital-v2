import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { type ComponentType } from 'react';
import ReactDOMServer from 'react-dom/server';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx');

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: async (name) => {
            const pageComponent = pages[`./pages/${name}.tsx`];

            if (typeof pageComponent !== 'function') {
                throw new Error(`Page not found: ${name}`);
            }

            const module = await pageComponent();

            return module.default;
        },
        setup: ({ App, props }) => {
            return <App {...props} />;
        },
    }),
);
