import { dashboard, login } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    Globe,
    HandHeart,
    Languages,
    Newspaper,
    Sparkles,
    Users,
} from 'lucide-react';

const boardMembers = [
    { initial: 'A', name: 'Aristarline Umwamikazi', role: 'Head of the Board' },
    { initial: 'T', name: 'Tyrone Muliza', role: 'Board Member' },
    { initial: 'L', name: 'Lemi Manoah', role: 'Board Member' },
    { initial: 'I', name: 'Ivan Ociti', role: 'Board Member' },
];

const investors = ['RCHA', 'RICTA'];

const milestones = [
    { year: '2025', title: 'Company founded' },
    { year: '2026', title: 'First product launched' },
    { year: '2027', title: 'Short-video platform launched' },
];

const culturePoints = [
    'Live and be driven by our mission and vision.',
    'Show patience and resilience.',
    'Solve problems together.',
    'Keep learning, keep pushing boundaries.',
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="QROO | QrooEMR">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800"
                    rel="stylesheet"
                />
            </Head>

            <div className="min-h-screen bg-[linear-gradient(180deg,#f8efe2_0%,#fffaf4_32%,#f4f1eb_100%)] text-zinc-950">
                <div className="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6 lg:px-10">
                    <header className="flex flex-col gap-5 rounded-[2rem] border border-white/70 bg-white/75 px-5 py-4 shadow-lg shadow-amber-950/5 backdrop-blur lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex size-11 items-center justify-center rounded-2xl bg-zinc-950 text-zinc-50 shadow-lg shadow-zinc-950/10">
                                <Sparkles className="h-5 w-5" />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <p className="text-xs font-semibold tracking-[0.32em] text-zinc-500 uppercase">
                                    QROO
                                </p>
                                <p className="text-sm font-medium text-zinc-700">
                                    QrooEMR on{' '}
                                    <span className="text-zinc-950">
                                        qroo.rw
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center">
                            <nav className="flex flex-wrap items-center gap-3 text-sm font-medium text-zinc-700">
                                <a
                                    href="#about"
                                    className="transition hover:text-zinc-950"
                                >
                                    About Us
                                </a>
                                <a
                                    href="#press"
                                    className="transition hover:text-zinc-950"
                                >
                                    Press
                                </a>
                                <a
                                    href="#careers"
                                    className="transition hover:text-zinc-950"
                                >
                                    Join Us
                                </a>
                            </nav>

                            <div className="flex items-center gap-3">
                                <div className="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm font-medium text-zinc-700">
                                    <Languages className="mr-2 h-4 w-4" />
                                    English
                                </div>

                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        className="rounded-full border border-zinc-300 bg-white px-5 py-2 text-sm font-medium text-zinc-900 transition hover:border-zinc-950"
                                    >
                                        Open dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={login()}
                                            className="rounded-full px-4 py-2 text-sm font-medium text-zinc-700 transition hover:text-zinc-950"
                                        >
                                            Log in
                                        </Link>
                                        <Link
                                            href="/create-workspace"
                                            className="inline-flex items-center rounded-full bg-zinc-950 px-5 py-2 text-sm font-medium text-zinc-50 transition hover:bg-zinc-800"
                                        >
                                            Try QrooEMR
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                    </header>

                    <main className="flex flex-1 flex-col gap-8 py-8 lg:gap-10 lg:py-10">
                        <section className="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
                            <div className="relative overflow-hidden rounded-[2rem] bg-zinc-950 px-6 py-8 text-zinc-50 shadow-2xl shadow-zinc-950/10 sm:px-8 sm:py-10">
                                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.3),transparent_26%),radial-gradient(circle_at_bottom_left,rgba(249,115,22,0.18),transparent_24%)]" />
                                <div className="relative flex flex-col gap-6">
                                    <div className="inline-flex w-fit items-center rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-sm font-medium text-zinc-100">
                                        <Globe className="mr-2 h-4 w-4" />
                                        qroo.rw
                                    </div>

                                    <div className="flex max-w-3xl flex-col gap-4">
                                        <p className="text-sm font-semibold tracking-[0.28em] text-amber-200 uppercase">
                                            QrooEMR
                                        </p>
                                        <h1 className="text-4xl leading-tight font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                                            Inspire Creativity, Enrich Life
                                        </h1>
                                        <p className="max-w-2xl text-lg leading-8 text-zinc-300">
                                            Our mission guides everything we do.
                                            We build platforms that help people
                                            connect, create, and discover.
                                        </p>
                                    </div>

                                    <div className="flex flex-col gap-3 sm:flex-row">
                                        {auth.user ? (
                                            <Link
                                                href={dashboard()}
                                                className="inline-flex items-center justify-center rounded-2xl bg-amber-300 px-6 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-amber-200"
                                            >
                                                Open QrooEMR
                                                <ArrowRight className="ml-2 h-4 w-4" />
                                            </Link>
                                        ) : (
                                            <>
                                                <Link
                                                    href="/create-workspace"
                                                    className="inline-flex items-center justify-center rounded-2xl bg-amber-300 px-6 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-amber-200"
                                                >
                                                    Start with QrooEMR
                                                    <ArrowRight className="ml-2 h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={login()}
                                                    className="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/8 px-6 py-3 text-sm font-medium text-zinc-50 transition hover:bg-white/12"
                                                >
                                                    Sign in
                                                </Link>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="flex flex-col gap-6 rounded-[2rem] border border-amber-100 bg-white/80 p-6 shadow-lg shadow-amber-950/5 backdrop-blur">
                                <div className="flex items-start gap-4">
                                    <div className="flex size-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                                        <HandHeart className="h-5 w-5" />
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase">
                                            Culture
                                        </p>
                                        <h2 className="text-2xl font-semibold text-zinc-950">
                                            Grow Together
                                        </h2>
                                        <p className="text-sm leading-7 text-zinc-600">
                                            Live and be driven by our mission
                                            and vision. Show patience and
                                            resilience. Solve problems together.
                                            Keep learning, keep pushing
                                            boundaries.
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-3">
                                    {culturePoints.map((point) => (
                                        <div
                                            key={point}
                                            className="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm leading-6 text-zinc-700"
                                        >
                                            {point}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </section>

                        <section className="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
                            <div
                                id="about"
                                className="rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-lg shadow-zinc-900/5 backdrop-blur sm:p-8"
                            >
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                                    <div className="flex flex-col gap-2">
                                        <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase">
                                            Board Members
                                        </p>
                                        <h2 className="text-3xl font-semibold tracking-tight text-zinc-950">
                                            Leadership shaping QROO
                                        </h2>
                                    </div>
                                    <div className="flex items-center gap-2 text-sm font-medium text-zinc-500">
                                        <span className="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5">
                                            Previous
                                        </span>
                                        <span className="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5">
                                            Next
                                        </span>
                                    </div>
                                </div>

                                <div className="mt-6 grid gap-4 sm:grid-cols-2">
                                    {boardMembers.map((member) => (
                                        <div
                                            key={member.name}
                                            className="rounded-[1.75rem] border border-zinc-200 bg-zinc-50/80 p-5"
                                        >
                                            <div className="flex items-center gap-4">
                                                <div className="flex size-12 items-center justify-center rounded-2xl bg-zinc-950 text-lg font-semibold text-zinc-50">
                                                    {member.initial}
                                                </div>
                                                <div className="flex flex-col gap-1">
                                                    <h3 className="text-lg font-semibold text-zinc-950">
                                                        {member.name}
                                                    </h3>
                                                    <p className="text-sm text-zinc-600">
                                                        {member.role}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="flex flex-col gap-6">
                                <div className="rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-lg shadow-zinc-900/5 backdrop-blur">
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                                            <Users className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase">
                                                Select Investors
                                            </p>
                                            <h2 className="text-2xl font-semibold text-zinc-950">
                                                Backing the journey
                                            </h2>
                                        </div>
                                    </div>

                                    <div className="mt-5 flex flex-wrap gap-3">
                                        {investors.map((investor) => (
                                            <div
                                                key={investor}
                                                className="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-base font-semibold text-zinc-900"
                                            >
                                                {investor}
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="rounded-[2rem] border border-zinc-900 bg-zinc-950 p-6 text-zinc-50 shadow-xl shadow-zinc-950/10">
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-2xl bg-white/10 text-zinc-50">
                                            <Building2 className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold tracking-[0.24em] text-zinc-400 uppercase">
                                                Product
                                            </p>
                                            <h2 className="text-2xl font-semibold">
                                                QrooEMR
                                            </h2>
                                        </div>
                                    </div>

                                    <p className="mt-4 text-sm leading-7 text-zinc-300">
                                        QrooEMR is QROO&apos;s first launched
                                        product, built to deliver modern
                                        healthcare operations through a clear,
                                        approachable experience.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                            <div className="rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-lg shadow-zinc-900/5 backdrop-blur sm:p-8">
                                <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase">
                                    Milestones
                                </p>
                                <h2 className="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">
                                    Key moments in our history.
                                </h2>

                                <div className="mt-6 flex flex-col gap-4">
                                    {milestones.map((item) => (
                                        <div
                                            key={item.year}
                                            className="flex items-center justify-between gap-4 rounded-[1.75rem] border border-zinc-200 bg-zinc-50/80 px-5 py-4"
                                        >
                                            <span className="text-lg font-semibold text-zinc-950">
                                                {item.year}
                                            </span>
                                            <span className="text-sm font-medium text-zinc-600">
                                                {item.title}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="rounded-[2rem] border border-white/70 bg-[linear-gradient(135deg,rgba(251,191,36,0.16),rgba(255,255,255,0.9),rgba(15,23,42,0.06))] p-6 shadow-lg shadow-zinc-900/5 sm:p-8">
                                <div className="flex flex-col gap-3">
                                    <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase">
                                        Mission
                                    </p>
                                    <h2 className="text-3xl font-semibold tracking-tight text-zinc-950">
                                        Building products that help people move
                                        forward
                                    </h2>
                                    <p className="max-w-2xl text-base leading-8 text-zinc-700">
                                        QROO is growing a portfolio of products
                                        led by QrooEMR. The company is focused
                                        on connection, creativity, and
                                        discovery, with healthcare as the first
                                        expression of that broader vision.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </main>

                    <footer className="rounded-[2rem] border border-zinc-200/80 bg-zinc-950 px-6 py-8 text-zinc-50 shadow-2xl shadow-zinc-950/10 sm:px-8">
                        <div className="grid gap-8 lg:grid-cols-[1.1fr_0.9fr_0.9fr_1.2fr]">
                            <div className="flex flex-col gap-4">
                                <p className="text-lg font-semibold">QROO</p>
                                <p className="text-sm leading-7 text-zinc-300">
                                    QrooEMR is live at qroo.rw as the first
                                    product in the QROO ecosystem.
                                </p>
                            </div>

                            <div
                                id="press"
                                className="flex flex-col gap-3 text-sm text-zinc-300"
                            >
                                <p className="font-semibold text-zinc-50">
                                    About
                                </p>
                                <a
                                    href="#about"
                                    className="transition hover:text-white"
                                >
                                    About Us
                                </a>
                                <a
                                    href="#press"
                                    className="transition hover:text-white"
                                >
                                    Press
                                </a>
                                <a
                                    href="#careers"
                                    className="transition hover:text-white"
                                >
                                    Join Us
                                </a>
                            </div>

                            <div
                                id="careers"
                                className="flex flex-col gap-3 text-sm text-zinc-300"
                            >
                                <p className="font-semibold text-zinc-50">
                                    Resources
                                </p>
                                <a
                                    href="#press"
                                    className="transition hover:text-white"
                                >
                                    Press
                                </a>
                                <a
                                    href="#"
                                    className="transition hover:text-white"
                                >
                                    Blog
                                </a>
                                <a
                                    href="#careers"
                                    className="transition hover:text-white"
                                >
                                    Careers
                                </a>
                                <a
                                    href="#careers"
                                    className="transition hover:text-white"
                                >
                                    Join Us
                                </a>
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-1">
                                <div className="flex flex-col gap-2 text-sm text-zinc-300">
                                    <p className="font-semibold text-zinc-50">
                                        Contact
                                    </p>
                                    <p>Media: press@qroo.rw</p>
                                    <p>Careers: hr@qroo.rw</p>
                                    <p>Advertising: advertise@qroo.rw</p>
                                    <p>Support: support@qroo.rw</p>
                                    <p>Sales: sales@qroo.rw</p>
                                </div>

                                <div className="flex flex-col gap-2 text-sm text-zinc-300">
                                    <p className="font-semibold text-zinc-50">
                                        Our Socials
                                    </p>
                                    <a
                                        href="#"
                                        className="transition hover:text-white"
                                    >
                                        X
                                    </a>
                                    <a
                                        href="#"
                                        className="transition hover:text-white"
                                    >
                                        Reddit
                                    </a>
                                    <a
                                        href="#"
                                        className="transition hover:text-white"
                                    >
                                        LinkedIn
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div className="mt-8 flex flex-col gap-3 border-t border-white/10 pt-6 text-sm text-zinc-400 sm:flex-row sm:items-center sm:justify-between">
                            <p>(c) 2026 Qroo. All rights reserved.</p>
                            <a
                                href="#"
                                className="inline-flex items-center gap-2 transition hover:text-white"
                            >
                                <Newspaper className="h-4 w-4" />
                                Privacy Policy
                            </a>
                        </div>
                    </footer>
                </div>
            </div>
        </>
    );
}
