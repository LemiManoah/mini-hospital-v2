import { dashboard, login } from '@/routes';
import { Link } from '@inertiajs/react';

function headerLinkClass(active = false) {
    return [
        'font-[Manrope] text-[10px] font-semibold uppercase tracking-[0.28em] transition-colors',
        active ? 'text-stone-100' : 'text-stone-500 hover:text-[#7ea2d6]',
    ].join(' ');
}

export function WelcomeHeader({ authenticated }: { authenticated: boolean }) {
    return (
        <header className="sticky top-0 z-50 border-b border-white/6 bg-[#0e0e0e]/90 backdrop-blur-md">
            <nav className="mx-auto flex h-16 w-full max-w-[1440px] items-center justify-between px-6 md:px-10">
                <div className="flex items-center gap-3">
                    <div className="size-3 bg-[#4A6FA5]" />
                    <span className="font-[Manrope] text-xl font-extrabold tracking-[-0.08em] text-stone-100 uppercase">
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

                <div className="flex items-center gap-3">
                    {authenticated ? (
                        <Link
                            href={dashboard()}
                            className="border border-white/10 px-4 py-2 font-[Manrope] text-[10px] font-bold uppercase tracking-[0.28em] text-stone-100 transition hover:border-[#4A6FA5] hover:text-[#9bb5da]"
                        >
                            Open Workspace
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={login()}
                                className="hidden font-[Manrope] text-[10px] font-bold uppercase tracking-[0.28em] text-stone-400 transition hover:text-stone-100 sm:inline-flex"
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
