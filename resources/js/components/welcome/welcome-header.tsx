import { useAppearance } from '@/hooks/use-appearance';
import { dashboard, login } from '@/routes';
import { Link } from '@inertiajs/react';
import { Moon, Sun } from 'lucide-react';

function headerLinkClass(active = false) {
    return [
        'font-[Manrope] text-[10px] font-semibold uppercase tracking-[0.28em] transition-colors',
        active
            ? 'text-stone-950 dark:text-stone-100'
            : 'text-stone-500 hover:text-[#4A6FA5] dark:text-stone-400 dark:hover:text-[#7ea2d6]',
    ].join(' ');
}

function themeButtonClass(active: boolean) {
    return [
        'inline-flex h-8 w-8 items-center justify-center rounded-full transition',
        active
            ? 'bg-stone-950 text-white shadow-sm dark:bg-white dark:text-stone-950'
            : 'text-stone-500 hover:text-stone-950 dark:text-stone-400 dark:hover:text-stone-100',
    ].join(' ');
}

export function WelcomeHeader({ authenticated }: { authenticated: boolean }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    return (
        <header className="sticky top-0 z-50 border-b border-stone-200 bg-stone-50/90 backdrop-blur-md dark:border-white/6 dark:bg-[#0e0e0e]/90">
            <nav className="mx-auto flex h-16 w-full max-w-[1440px] items-center justify-between gap-4 px-6 md:px-10">
                <div className="flex items-center gap-3">
                    <div className="size-3 bg-[#4A6FA5]" />
                    <span className="font-[Manrope] text-xl font-extrabold tracking-[-0.08em] text-stone-950 uppercase dark:text-stone-100">
                        Qroo
                    </span>
                </div>

                <div className="hidden items-center gap-10 md:flex">
                    <a href="#home" className={headerLinkClass(true)}>
                        Home
                    </a>
                    <a href="#solutions" className={headerLinkClass()}>
                        Solutions
                    </a>
                    <a href="#evidence" className={headerLinkClass()}>
                        Proof
                    </a>
                </div>

                <div className="flex items-center gap-2 sm:gap-3">
                    <div className="flex items-center rounded-full border border-stone-200 bg-white/80 p-0.5 shadow-sm dark:border-white/10 dark:bg-white/5 dark:shadow-none">
                        <button
                            type="button"
                            className={themeButtonClass(resolvedAppearance === 'light')}
                            onClick={() => updateAppearance('light')}
                            aria-label="Use light mode"
                            title="Light mode"
                        >
                            <Sun className="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            className={themeButtonClass(resolvedAppearance === 'dark')}
                            onClick={() => updateAppearance('dark')}
                            aria-label="Use dark mode"
                            title="Dark mode"
                        >
                            <Moon className="h-3.5 w-3.5" />
                        </button>
                    </div>

                    {authenticated ? (
                        <Link
                            href={dashboard()}
                            className="border border-stone-200 bg-white px-4 py-2 font-[Manrope] text-[10px] font-bold uppercase tracking-[0.28em] text-stone-950 transition hover:border-[#4A6FA5] hover:text-[#4A6FA5] dark:border-white/10 dark:bg-transparent dark:text-stone-100 dark:hover:border-[#4A6FA5] dark:hover:text-[#9bb5da]"
                        >
                            Open Workspace
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={login()}
                                className="hidden font-[Manrope] text-[10px] font-bold uppercase tracking-[0.28em] text-stone-500 transition hover:text-stone-950 sm:inline-flex dark:text-stone-400 dark:hover:text-stone-100"
                            >
                                Log In
                            </Link>
                            <Link
                                href="/create-workspace"
                                className="bg-[#4A6FA5] px-5 py-2 font-[Manrope] text-[10px] font-bold uppercase tracking-[0.28em] text-white transition hover:bg-[#5f84bb]"
                            >
                                Get Started
                            </Link>
                        </>
                    )}
                </div>
            </nav>
        </header>
    );
}
