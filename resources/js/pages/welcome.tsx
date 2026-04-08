import { dashboard, login } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Brain,
    Building2,
    Cloud,
    Code2,
    Globe,
    Languages,
    Newspaper,
    Palette,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-react';

const emrModules = [
    {
        title: 'Patient & Visit Records',
        description:
            'A fast, searchable EMR foundation for charts, history, and day-to-day clinical workflows.',
        icon: <Users className="h-5 w-5" />,
    },
    {
        title: 'Pharmacy & Inventory Workflows',
        description:
            'Medication availability, stock visibility, and operational tooling aligned with clinical activity.',
        icon: <Building2 className="h-5 w-5" />,
    },
    {
        title: 'Documentation That Stays Consistent',
        description:
            'Structured notes and clean UI patterns designed to reduce friction for staff and improve record quality.',
        icon: <Sparkles className="h-5 w-5" />,
    },
    {
        title: 'Admin, Roles & Access Control',
        description:
            'Role-aware organization tools so teams can work safely and administrators can manage the system confidently.',
        icon: <ShieldCheck className="h-5 w-5" />,
    },
];

const services = [
    {
        title: 'Web Development',
        description:
            'Professional web apps and dashboards—built to be maintainable, responsive, and fast.',
        icon: <Code2 className="h-5 w-5" />,
        bullets: ['Inertia + React UI', 'API integration', 'Performance-first frontends'],
    },
    {
        title: 'System Development',
        description:
            'Back-office systems, internal tools, and healthcare workflows that won’t collapse under real usage.',
        icon: <Building2 className="h-5 w-5" />,
        bullets: ['Action-oriented architecture', 'Clear data modeling', 'E2E workflow coverage'],
    },
    {
        title: 'AI Solutions',
        description:
            'AI-assisted features that complement clinical work—summaries, smart suggestions, and automation where it helps.',
        icon: <Brain className="h-5 w-5" />,
        bullets: ['Workflow automation', 'Assistive insights', 'Human-in-the-loop design'],
    },
    {
        title: 'UI/UX Design',
        description:
            'High-conversion, healthcare-aware UI design. We build interfaces that feel premium and reduce user effort.',
        icon: <Palette className="h-5 w-5" />,
        bullets: ['UX prototypes', 'Design systems', 'Usability-focused iterations'],
    },
    {
        title: 'Hosting & Managed Ops',
        description:
            'Deployment, monitoring, updates, and reliability support so your software stays available for patients and staff.',
        icon: <Cloud className="h-5 w-5" />,
        bullets: ['Secure deployments', 'Monitoring & alerts', 'Maintenance plans'],
    },
];

const processSteps = [
    {
        title: 'Discover',
        description:
            'We map your workflows, constraints, and success metrics—then define a practical build plan.',
    },
    {
        title: 'Design',
        description:
            'We craft UI/UX that looks premium and works under pressure, with rapid prototype feedback.',
    },
    {
        title: 'Build',
        description:
            'We implement reliable features using clean architecture and composable components.',
    },
    {
        title: 'AI Assist (Optional)',
        description:
            'We add AI where it improves speed and clarity—always designed to keep humans in control.',
    },
    {
        title: 'Deploy & Improve',
        description:
            'We ship, monitor, and iterate with ongoing optimization and support.',
    },
];

const securityPoints = [
    {
        title: 'Access Control',
        description:
            'Role-aware permissions and safer default patterns so the right people see the right data.',
    },
    {
        title: 'Audit-Friendly Workflows',
        description:
            'Operational design that supports traceability and consistent clinical records.',
    },
    {
        title: 'Secure-by-Design Delivery',
        description:
            'We build with secure engineering habits—so deployment and growth don’t become risk multipliers.',
    },
];

const pricingTiers = [
    {
        name: 'Starter',
        description: 'For teams launching EMR quickly with core workflows.',
        price: 'Talk to us',
        bullets: ['Core QrooEMR modules', 'Guided rollout', 'Email support'],
    },
    {
        name: 'Growth',
        description: 'For multi-department operations and smoother staff adoption.',
        price: 'Talk to us',
        bullets: ['Workflow optimization', 'AI-ready enhancements', 'Priority support'],
    },
    {
        name: 'Enterprise',
        description: 'For organizations needing deeper system development and managed ops.',
        price: 'Custom',
        bullets: ['System integrations', 'Hosting & managed reliability', 'Solution engineering'],
    },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="QrooEMR | QROO">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800"
                    rel="stylesheet"
                />
            </Head>

            <div className="min-h-screen bg-[linear-gradient(180deg,#f8efe2_0%,#fffaf4_32%,#f4f1eb_100%)] text-zinc-950 dark:bg-zinc-950 dark:text-zinc-50">
                <div className="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6 lg:px-10">
                    <header className="flex flex-col gap-5 rounded-4xl border border-white/70 bg-white/75 px-5 py-4 shadow-lg shadow-amber-950/5 backdrop-blur dark:border-white/10 dark:bg-zinc-950/60 lg:flex-row lg:items-center lg:justify-between">
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
                            <nav className="flex flex-wrap items-center gap-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                <a href="#modules" className="transition hover:text-zinc-950 dark:hover:text-zinc-50">
                                    Modules
                                </a>
                                <a href="#services" className="transition hover:text-zinc-950 dark:hover:text-zinc-50">
                                    Services
                                </a>
                                <a href="#process" className="transition hover:text-zinc-950 dark:hover:text-zinc-50">
                                    How it works
                                </a>
                                <a href="#security" className="transition hover:text-zinc-950 dark:hover:text-zinc-50">
                                    Security
                                </a>
                                <a href="#pricing" className="transition hover:text-zinc-950 dark:hover:text-zinc-50">
                                    Pricing
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
                                        className="rounded-full border border-zinc-300 bg-white px-5 py-2 text-sm font-medium text-zinc-900 transition hover:border-zinc-950 dark:bg-zinc-900/40 dark:text-zinc-50"
                                    >
                                        Open QrooEMR
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={login()}
                                            className="rounded-full px-4 py-2 text-sm font-medium text-zinc-700 transition hover:text-zinc-950 dark:text-zinc-200 dark:hover:text-zinc-50"
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

                                {!auth.user && (
                                    <a
                                        href="mailto:sales@qroo.rw?subject=QrooEMR%20Demo%20Request"
                                        className="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/8 px-5 py-2 text-sm font-medium text-zinc-700 transition hover:bg-white/12 dark:border-white/10 dark:bg-zinc-950/40 dark:text-zinc-200"
                                    >
                                        Request a demo
                                    </a>
                                )}
                            </div>
                        </div>
                    </header>

                    <main className="flex flex-1 flex-col gap-12 py-8 lg:gap-14 lg:py-10">
                        <section className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                            <div className="relative overflow-hidden rounded-4xl bg-zinc-950 px-6 py-8 text-zinc-50 shadow-2xl shadow-zinc-950/10 sm:px-8 sm:py-10">
                                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.34),transparent_26%),radial-gradient(circle_at_bottom_left,rgba(249,115,22,0.22),transparent_24%)]" />
                                <div className="relative flex flex-col gap-7">
                                    <div className="inline-flex w-fit items-center rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-sm font-medium text-zinc-100">
                                        <Globe className="mr-2 h-4 w-4" />
                                        qroo.rw
                                    </div>

                                    <div className="flex max-w-3xl flex-col gap-4">
                                        <p className="text-sm font-semibold tracking-[0.28em] text-amber-200 uppercase">
                                            QrooEMR
                                        </p>
                                        <h1 className="text-4xl leading-tight font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                                            Electronic Medical Records,
                                            engineered to feel calm.
                                        </h1>
                                        <p className="max-w-2xl text-lg leading-8 text-zinc-300">
                                            QrooEMR is QROO&apos;s electronic medical records system—built for fast documentation, reliable operations, and a modern clinical experience.
                                        </p>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                            <p className="text-sm font-semibold">Faster charting</p>
                                            <p className="mt-1 text-sm text-zinc-300">
                                                Clear UI patterns that reduce time per visit.
                                            </p>
                                        </div>
                                        <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                            <p className="text-sm font-semibold">Pharmacy-aware ops</p>
                                            <p className="mt-1 text-sm text-zinc-300">
                                                Medication and inventory workflows designed for reality.
                                            </p>
                                        </div>
                                        <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                            <p className="text-sm font-semibold">AI-ready workflows</p>
                                            <p className="mt-1 text-sm text-zinc-300">
                                                Optional AI assistance to improve speed and clarity.
                                            </p>
                                        </div>
                                        <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                            <p className="text-sm font-semibold">Secure operations</p>
                                            <p className="mt-1 text-sm text-zinc-300">
                                                Role-aware access patterns built into administration.
                                            </p>
                                        </div>
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
                                                    Try QrooEMR
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

                            <div className="flex flex-col gap-4 rounded-4xl border border-white/70 bg-white/80 p-6 shadow-lg shadow-amber-950/5 backdrop-blur dark:border-white/10 dark:bg-zinc-950/50">
                                <div className="flex items-start gap-4">
                                    <div className="flex size-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-200">
                                        <Sparkles className="h-5 w-5" />
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase dark:text-zinc-300">
                                            Software Company
                                        </p>
                                        <h2 className="text-2xl font-semibold text-zinc-950 dark:text-zinc-50">
                                            QROO builds what healthcare teams need.
                                        </h2>
                                        <p className="text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                            Web development, system development, AI solutions, UI/UX design, and hosting—delivered with production-ready engineering.
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-3">
                                    <div className="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200">
                                        <p className="font-semibold">Primary product</p>
                                        <p className="mt-1">
                                            <span className="font-semibold text-zinc-950 dark:text-zinc-50">QrooEMR</span> — Electronic Medical Records system.
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200">
                                        <p className="font-semibold">What you get</p>
                                        <p className="mt-1">A premium UI + reliable workflows, designed for daily clinical use.</p>
                                    </div>
                                    <div className="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200">
                                        <p className="font-semibold">Next step</p>
                                        <p className="mt-1">
                                            {auth.user ? 'Open your workspace and start exploring.' : 'Request a demo or start a workspace in minutes.'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section id="modules" className="flex flex-col gap-6">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                <div className="flex flex-col gap-2">
                                    <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase dark:text-zinc-300">
                                        QrooEMR Modules
                                    </p>
                                    <h2 className="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">
                                        Everything that matters in a modern EMR
                                    </h2>
                                </div>
                                <p className="max-w-lg text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                    Built around real workflows: documentation speed, operational clarity, and admin confidence.
                                </p>
                            </div>

                            <div className="grid gap-4 lg:grid-cols-2">
                                {emrModules.map((module) => (
                                    <div
                                        key={module.title}
                                        className="rounded-4xl border border-white/70 bg-white/80 p-6 shadow-lg shadow-zinc-900/5 backdrop-blur dark:border-white/10 dark:bg-zinc-950/50"
                                    >
                                        <div className="flex items-start gap-4">
                                            <div className="flex size-12 items-center justify-center rounded-2xl bg-zinc-950 text-zinc-50 shadow-lg shadow-zinc-950/10 dark:bg-white/10 dark:text-zinc-50">
                                                {module.icon}
                                            </div>
                                            <div className="flex flex-col gap-2">
                                                <h3 className="text-xl font-semibold text-zinc-950 dark:text-zinc-50">
                                                    {module.title}
                                                </h3>
                                                <p className="text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                                    {module.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section id="services" className="flex flex-col gap-6">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                <div className="flex flex-col gap-2">
                                    <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase dark:text-zinc-300">
                                        Services
                                    </p>
                                    <h2 className="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">
                                        Software development, designed for outcomes
                                    </h2>
                                </div>
                                <p className="max-w-lg text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                    Whether you need a new system or enhancements to QrooEMR, we deliver modern engineering with a premium user experience.
                                </p>
                            </div>

                            <div className="grid gap-4 lg:grid-cols-2">
                                {services.map((service) => (
                                    <div
                                        key={service.title}
                                        className="rounded-4xl border border-white/70 bg-white/80 p-6 shadow-lg shadow-zinc-900/5 backdrop-blur dark:border-white/10 dark:bg-zinc-950/50"
                                    >
                                        <div className="flex items-start gap-4">
                                            <div className="flex size-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-200">
                                                {service.icon}
                                            </div>
                                            <div className="flex flex-col gap-2">
                                                <h3 className="text-xl font-semibold text-zinc-950 dark:text-zinc-50">
                                                    {service.title}
                                                </h3>
                                                <p className="text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                                    {service.description}
                                                </p>
                                                <ul className="mt-3 flex flex-wrap gap-2">
                                                    {service.bullets.map((bullet) => (
                                                        <li
                                                            key={bullet}
                                                            className="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200"
                                                        >
                                                            {bullet}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section id="process" className="flex flex-col gap-6">
                            <div className="flex flex-col gap-2">
                                <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase dark:text-zinc-300">
                                    How it works
                                </p>
                                <h2 className="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">
                                    From discovery to deployment—without chaos
                                </h2>
                            </div>

                            <div className="grid gap-4 lg:grid-cols-5">
                                {processSteps.map((step, index) => (
                                    <div
                                        key={step.title}
                                        className="relative rounded-4xl border border-white/70 bg-white/80 p-5 shadow-lg shadow-zinc-900/5 backdrop-blur dark:border-white/10 dark:bg-zinc-950/50 lg:col-span-1"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-10 items-center justify-center rounded-2xl bg-zinc-950 text-zinc-50 dark:bg-white/10">
                                                <span className="text-sm font-semibold">{index + 1}</span>
                                            </div>
                                            <h3 className="text-lg font-semibold text-zinc-950 dark:text-zinc-50">
                                                {step.title}
                                            </h3>
                                        </div>
                                        <p className="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                            {step.description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section id="security" className="grid gap-6 lg:grid-cols-[1fr_1fr]">
                            <div className="rounded-4xl border border-white/70 bg-white/80 p-6 shadow-lg shadow-zinc-900/5 backdrop-blur dark:border-white/10 dark:bg-zinc-950/50">
                                <div className="flex flex-col gap-2">
                                    <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase dark:text-zinc-300">
                                        Security
                                    </p>
                                    <h2 className="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">
                                        Built with access control in mind
                                    </h2>
                                    <p className="text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                        Healthcare systems need careful operational design. We focus on safer defaults and admin-friendly workflows.
                                    </p>
                                </div>
                            </div>

                            <div className="grid gap-4">
                                {securityPoints.map((point) => (
                                    <div
                                        key={point.title}
                                        className="rounded-4xl border border-white/70 bg-white/80 p-6 shadow-lg shadow-zinc-900/5 backdrop-blur dark:border-white/10 dark:bg-zinc-950/50"
                                    >
                                        <div className="flex items-start gap-4">
                                            <div className="flex size-12 items-center justify-center rounded-2xl bg-zinc-950 text-zinc-50 shadow-lg shadow-zinc-950/10 dark:bg-white/10">
                                                <ShieldCheck className="h-5 w-5" />
                                            </div>
                                            <div className="flex flex-col gap-2">
                                                <h3 className="text-xl font-semibold text-zinc-950 dark:text-zinc-50">
                                                    {point.title}
                                                </h3>
                                                <p className="text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                                    {point.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section id="pricing" className="flex flex-col gap-6">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                <div className="flex flex-col gap-2">
                                    <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase dark:text-zinc-300">
                                        Pricing
                                    </p>
                                    <h2 className="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">
                                        Plans that match how teams grow
                                    </h2>
                                </div>
                                <p className="max-w-lg text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                    We tailor scope and delivery. If you want QrooEMR + custom system work, we’ll propose a clean plan.
                                </p>
                            </div>

                            <div className="grid gap-4 lg:grid-cols-3">
                                {pricingTiers.map((tier, index) => (
                                    <div
                                        key={tier.name}
                                        className={[
                                            'rounded-4xl border bg-white/80 p-6 shadow-lg shadow-zinc-900/5 backdrop-blur dark:bg-zinc-950/50 dark:border-white/10',
                                            index === 1
                                                ? 'border-amber-200'
                                                : 'border-white/70',
                                        ].join(' ')}
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex flex-col gap-2">
                                                <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase dark:text-zinc-300">
                                                    {tier.name}
                                                </p>
                                                <h3 className="text-2xl font-semibold text-zinc-950 dark:text-zinc-50">
                                                    {tier.price}
                                                </h3>
                                            </div>
                                            {index === 1 ? (
                                                <div className="rounded-full border border-amber-200 bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900 dark:bg-amber-400/10 dark:text-amber-200">
                                                    Most chosen
                                                </div>
                                            ) : null}
                                        </div>
                                        <p className="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                            {tier.description}
                                        </p>
                                        <ul className="mt-5 flex flex-col gap-3">
                                            {tier.bullets.map((bullet) => (
                                                <li
                                                    key={bullet}
                                                    className="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-200"
                                                >
                                                    <span className="mt-1 size-2 rounded-full bg-amber-400" />
                                                    <span>{bullet}</span>
                                                </li>
                                            ))}
                                        </ul>
                                        <div className="mt-6">
                                            <a
                                                href="mailto:sales@qroo.rw?subject=QrooEMR%20-%20Pricing%20Inquiry"
                                                className="inline-flex w-full items-center justify-center rounded-2xl bg-zinc-950 px-5 py-3 text-sm font-semibold text-zinc-50 transition hover:bg-zinc-800"
                                            >
                                                Contact sales
                                                <ArrowRight className="ml-2 h-4 w-4" />
                                            </a>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section
                            id="contact"
                            className="rounded-4xl border border-white/70 bg-[linear-gradient(135deg,rgba(251,191,36,0.16),rgba(255,255,255,0.9),rgba(15,23,42,0.06))] p-6 shadow-lg shadow-zinc-900/5 backdrop-blur sm:p-8 dark:bg-white/5 dark:border-white/10"
                        >
                            <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                                <div className="flex flex-col gap-3">
                                    <p className="text-sm font-semibold tracking-[0.24em] text-zinc-500 uppercase dark:text-zinc-300">
                                        Start a conversation
                                    </p>
                                    <h2 className="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">
                                        Want QrooEMR, AI features, or a custom system?
                                    </h2>
                                    <p className="text-sm leading-7 text-zinc-600 dark:text-zinc-200">
                                        Email our team and we’ll respond with a clear scope, timeline, and next steps.
                                    </p>
                                </div>
                                <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                    {auth.user ? (
                                        <Link
                                            href={dashboard()}
                                            className="inline-flex items-center justify-center rounded-2xl bg-amber-300 px-6 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-amber-200"
                                        >
                                            Go to dashboard
                                            <ArrowRight className="ml-2 h-4 w-4" />
                                        </Link>
                                    ) : (
                                        <Link
                                            href="/create-workspace"
                                            className="inline-flex items-center justify-center rounded-2xl bg-amber-300 px-6 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-amber-200"
                                        >
                                            Try QrooEMR
                                            <ArrowRight className="ml-2 h-4 w-4" />
                                        </Link>
                                    )}
                                    <a
                                        href="mailto:sales@qroo.rw?subject=QrooEMR%20-%20Request%20a%20Demo"
                                        className="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white/60 px-6 py-3 text-sm font-medium text-zinc-800 transition hover:bg-white/80 dark:border-white/10 dark:bg-zinc-950/40 dark:text-zinc-50 dark:hover:bg-zinc-950/30"
                                    >
                                        Request a demo
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </section>
                    </main>

                    <footer className="rounded-4xl border border-zinc-200/80 bg-zinc-950 px-6 py-8 text-zinc-50 shadow-2xl shadow-zinc-950/10 sm:px-8">
                        <div className="grid gap-8 lg:grid-cols-[1.1fr_0.9fr_0.9fr_1.2fr]">
                            <div className="flex flex-col gap-4">
                                <p className="text-lg font-semibold">QROO</p>
                                <p className="text-sm leading-7 text-zinc-300">
                                    QrooEMR is the primary product in the QROO ecosystem: an electronic medical records system built for modern clinical operations.
                                </p>
                            </div>

                            <div className="flex flex-col gap-3 text-sm text-zinc-300">
                                <p className="font-semibold text-zinc-50">Navigate</p>
                                <a href="#modules" className="transition hover:text-white">
                                    Modules
                                </a>
                                <a href="#services" className="transition hover:text-white">
                                    Services
                                </a>
                                <a href="#process" className="transition hover:text-white">
                                    How it works
                                </a>
                                <a href="#security" className="transition hover:text-white">
                                    Security
                                </a>
                                <a href="#pricing" className="transition hover:text-white">
                                    Pricing
                                </a>
                            </div>

                            <div className="flex flex-col gap-3 text-sm text-zinc-300">
                                <p className="font-semibold text-zinc-50">Contact</p>
                                <p>Sales: sales@qroo.rw</p>
                                <p>Support: support@qroo.rw</p>
                                <p>Careers: hr@qroo.rw</p>
                                <p>Media: press@qroo.rw</p>
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-1">
                                <div className="flex flex-col gap-2 text-sm text-zinc-300">
                                    <p className="font-semibold text-zinc-50">Social</p>
                                    <a href="#" className="transition hover:text-white">
                                        X
                                    </a>
                                    <a href="#" className="transition hover:text-white">
                                        LinkedIn
                                    </a>
                                </div>

                                <div className="flex flex-col gap-2 text-sm text-zinc-300">
                                    <p className="font-semibold text-zinc-50">Legal</p>
                                    <a href="#" className="inline-flex items-center gap-2 transition hover:text-white">
                                        <Newspaper className="h-4 w-4" />
                                        Privacy Policy
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div className="mt-8 flex flex-col gap-3 border-t border-white/10 pt-6 text-sm text-zinc-400 sm:flex-row sm:items-center sm:justify-between">
                            <p>(c) 2026 Qroo. All rights reserved.</p>
                            <a
                                href="mailto:sales@qroo.rw?subject=QrooEMR%20-%20General%20Inquiry"
                                className="inline-flex items-center gap-2 transition hover:text-white"
                            >
                                <ArrowRight className="h-4 w-4" />
                                Email us
                            </a>
                        </div>
                    </footer>
                </div>
            </div>
        </>
    );
}
