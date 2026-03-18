import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
    contentClassName?: string;
}

export default function AuthSplitLayout({
    children,
    title,
    description,
    contentClassName,
}: PropsWithChildren<AuthLayoutProps>) {
    const { name } = usePage<SharedData>().props;

    return (
        <div className="relative min-h-svh bg-background lg:grid lg:grid-cols-[1.05fr_0.95fr]">
            <div className="relative hidden min-h-svh flex-col overflow-hidden bg-muted p-10 text-white lg:flex dark:border-r">
                <div className="absolute inset-0 bg-zinc-900" />
                <Link
                    href={home()}
                    className="relative z-20 flex items-center text-lg font-medium"
                >
                    <AppLogoIcon className="mr-2 size-8 fill-current text-white" />
                    {name}
                </Link>
                <div className="relative z-20 mt-auto">
                    <blockquote className="space-y-2">
                        <p className="text-lg">
                            &ldquo;Simplicity is the ultimate
                            sophistication.&rdquo;
                        </p>
                        <footer className="text-sm text-neutral-300">
                            Leonardo da Vinci
                        </footer>
                    </blockquote>
                </div>
            </div>
            <div className="flex min-h-svh w-full items-center justify-center px-6 py-10 sm:px-8 lg:px-10">
                <div
                    className={cn(
                        'mx-auto flex w-full max-w-md flex-col justify-center space-y-6',
                        contentClassName,
                    )}
                >
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <AppLogoIcon className="h-10 fill-current text-black sm:h-12" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-sm text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
