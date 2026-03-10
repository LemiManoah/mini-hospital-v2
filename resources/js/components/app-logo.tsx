import AppLogoIcon from './app-logo-icon';
import { usePage } from '@inertiajs/react';

interface PageProps {
    auth?: {
        user?: {
            tenant?: {
                name: string;
            };
        };
    };
}

export default function AppLogo() {
    const { auth } = (usePage().props || {}) as PageProps;
    const tenant = auth?.user?.tenant;
    
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {tenant?.name || 'Mini Hospital'}
                </span>
            </div>
        </>
    );
}
