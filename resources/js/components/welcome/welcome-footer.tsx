import { dashboard, login } from '@/routes';
import { Link } from '@inertiajs/react';
import { type FooterColumn } from './types';

export function WelcomeFooter({
    footerColumns,
    authenticated,
}: {
    footerColumns: FooterColumn[];
    authenticated: boolean;
}) {
    return (
        <footer className="border-t border-stone-200 bg-stone-100 px-6 py-16 md:px-10 dark:border-white/6 dark:bg-[#080808]">
            <div className="mx-auto max-w-7xl">
                <div className="mb-16 grid gap-12 md:grid-cols-12">
                    <div className="md:col-span-4">
                        <div className="mb-6 flex items-center gap-3">
                            <div className="size-3 bg-[#4A6FA5]" />
                            <span className="font-[Manrope] text-xl font-extrabold tracking-[-0.08em] text-stone-950 uppercase dark:text-stone-100">
                                Qroo
                            </span>
                        </div>
                        <p className="max-w-xs text-xs leading-7 text-stone-600 dark:text-stone-500">
                            QrooEMR is a hospital workflow platform focused on
                            calm interfaces, operational structure, and
                            growth-ready clinical tooling.
                        </p>
                        <p className="mt-8 text-[10px] font-semibold tracking-[0.28em] text-stone-500 uppercase dark:text-stone-700">
                            (c) 2026 Qroo Systems
                        </p>
                    </div>

                    <div className="grid gap-8 md:col-span-8 md:grid-cols-4">
                        {footerColumns.map((column) => (
                            <div
                                key={column.title}
                                className="flex flex-col gap-5"
                            >
                                <span className="text-[9px] font-bold tracking-[0.28em] text-stone-950 uppercase dark:text-stone-100">
                                    {column.title}
                                </span>
                                {column.links.map((item) => (
                                    <a
                                        key={item}
                                        href="#"
                                        className="text-[11px] font-medium text-stone-600 transition hover:text-[#4A6FA5] dark:text-stone-500 dark:hover:text-[#7ea2d6]"
                                    >
                                        {item}
                                    </a>
                                ))}
                            </div>
                        ))}

                        <div className="flex flex-col gap-5">
                            <span className="text-[9px] font-bold tracking-[0.28em] text-stone-950 uppercase dark:text-stone-100">
                                Access
                            </span>
                            {authenticated ? (
                                <Link
                                    href={dashboard()}
                                    className="text-[11px] font-medium text-stone-600 transition hover:text-[#4A6FA5] dark:text-stone-500 dark:hover:text-[#7ea2d6]"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="text-[11px] font-medium text-stone-600 transition hover:text-[#4A6FA5] dark:text-stone-500 dark:hover:text-[#7ea2d6]"
                                    >
                                        Login
                                    </Link>
                                    <Link
                                        href="/create-workspace"
                                        className="text-[11px] font-medium text-stone-600 transition hover:text-[#4A6FA5] dark:text-stone-500 dark:hover:text-[#7ea2d6]"
                                    >
                                        Create Workspace
                                    </Link>
                                </>
                            )}
                            <a
                                href="mailto:sales@qroo.rw?subject=QrooEMR%20General%20Inquiry"
                                className="text-[11px] font-medium text-stone-600 transition hover:text-[#4A6FA5] dark:text-stone-500 dark:hover:text-[#7ea2d6]"
                            >
                                sales@qroo.rw
                            </a>
                        </div>
                    </div>
                </div>

                <div className="flex flex-col gap-4 border-t border-stone-200 pt-8 md:flex-row md:items-center md:justify-between dark:border-white/6">
                    <div className="flex gap-8">
                        <a
                            href="#"
                            className="text-[9px] font-semibold tracking-[0.28em] text-stone-500 uppercase transition hover:text-stone-900 dark:text-stone-700 dark:hover:text-stone-300"
                        >
                            Privacy Policy
                        </a>
                        <a
                            href="#"
                            className="text-[9px] font-semibold tracking-[0.28em] text-stone-500 uppercase transition hover:text-stone-900 dark:text-stone-700 dark:hover:text-stone-300"
                        >
                            Terms of Service
                        </a>
                    </div>

                    <span className="text-[9px] font-semibold tracking-[0.28em] text-stone-500 uppercase dark:text-stone-700">
                        v2.0 Clinical Build
                    </span>
                </div>
            </div>
        </footer>
    );
}
