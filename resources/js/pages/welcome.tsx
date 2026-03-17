import { dashboard, login } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CreditCard,
    ShieldCheck,
    Stethoscope,
    Workflow,
} from 'lucide-react';

const highlights = [
    {
        title: 'Self-serve workspace setup',
        description:
            'Create a hospital workspace, pick a package, and enter guided onboarding without manual seeding.',
        icon: Building2,
    },
    {
        title: 'Operational outpatient core',
        description:
            'Patients, appointments, visits, triage, consultations, and clinical orders already live in the product.',
        icon: Stethoscope,
    },
    {
        title: 'Multi-tenant control',
        description:
            'Tenant-aware and branch-aware architecture is already in place for secure hospital separation.',
        icon: ShieldCheck,
    },
    {
        title: 'Phase 0 in motion',
        description:
            'This release starts SaaS onboarding with workspace registration and a dedicated onboarding checkpoint.',
        icon: Workflow,
    },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Mini Hospital SaaS">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700"
                    rel="stylesheet"
                />
            </Head>

            <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(15,23,42,0.08),_transparent_30%),linear-gradient(180deg,#f7f6f2_0%,#ece8de_100%)] text-zinc-950">
                <div className="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 lg:px-10">
                    <header className="flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-zinc-950 text-zinc-50 shadow-lg shadow-zinc-950/10">
                                <Building2 className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm tracking-[0.24em] text-zinc-500 uppercase">
                                    Mini Hospital
                                </p>
                                <p className="text-sm font-medium text-zinc-700">
                                    Multi-tenant hospital operations platform
                                </p>
                            </div>
                        </div>

                        <nav className="flex items-center gap-3">
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
                                        Create workspace
                                    </Link>
                                </>
                            )}
                        </nav>
                    </header>

                    <main className="grid flex-1 gap-12 py-12 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                        <section className="space-y-8">
                            <div className="inline-flex items-center rounded-full border border-amber-300 bg-amber-100 px-4 py-1.5 text-sm font-medium text-amber-900">
                                <CreditCard className="mr-2 h-4 w-4" />
                                Phase 0 SaaS onboarding has started
                            </div>

                            <div className="space-y-5">
                                <h1 className="max-w-3xl text-4xl leading-tight font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                                    Launch hospital workspaces without manual
                                    setup.
                                </h1>
                                <p className="max-w-2xl text-lg leading-8 text-zinc-700">
                                    Mini Hospital is growing from a powerful
                                    internal hospital app into a true SaaS
                                    platform. New organizations can now begin
                                    with a dedicated workspace registration flow
                                    and step into onboarding immediately.
                                </p>
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Link
                                    href="/create-workspace"
                                    className="inline-flex items-center justify-center rounded-2xl bg-zinc-950 px-6 py-3 text-sm font-medium text-zinc-50 transition hover:bg-zinc-800"
                                >
                                    Start your workspace
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                                <Link
                                    href={login()}
                                    className="inline-flex items-center justify-center rounded-2xl border border-zinc-300 bg-white px-6 py-3 text-sm font-medium text-zinc-900 transition hover:border-zinc-950"
                                >
                                    Sign in to an existing tenant
                                </Link>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                {highlights.map((item) => (
                                    <div
                                        key={item.title}
                                        className="rounded-3xl border border-white/70 bg-white/80 p-5 shadow-sm shadow-zinc-200/40 backdrop-blur"
                                    >
                                        <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-zinc-950 text-zinc-50">
                                            <item.icon className="h-5 w-5" />
                                        </div>
                                        <h2 className="text-lg font-medium">
                                            {item.title}
                                        </h2>
                                        <p className="mt-2 text-sm leading-6 text-zinc-600">
                                            {item.description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className="rounded-[2rem] border border-zinc-200/70 bg-zinc-950 p-6 text-zinc-50 shadow-2xl shadow-zinc-950/10">
                            <div className="space-y-6">
                                <div>
                                    <p className="text-sm tracking-[0.24em] text-zinc-400 uppercase">
                                        Slice 1 Milestone
                                    </p>
                                    <h2 className="mt-3 text-2xl font-semibold">
                                        Workspace registration to onboarding
                                    </h2>
                                </div>

                                <div className="space-y-4">
                                    {[
                                        'Public landing page refreshed for SaaS positioning',
                                        'New workspace signup route and provisioning flow',
                                        'Transactional creation of tenant, first staff record, and owner user',
                                        'Automatic redirect into onboarding after signup',
                                    ].map((item) => (
                                        <div
                                            key={item}
                                            className="flex items-start gap-3 rounded-2xl border border-zinc-800 bg-zinc-900/80 p-4"
                                        >
                                            <div className="mt-0.5 h-2.5 w-2.5 rounded-full bg-emerald-400" />
                                            <p className="text-sm leading-6 text-zinc-200">
                                                {item}
                                            </p>
                                        </div>
                                    ))}
                                </div>

                                <div className="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
                                    <p className="text-sm text-zinc-300">
                                        Next up after this slice: primary branch
                                        setup, subscription activation, and a
                                        fully guided onboarding wizard.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>
        </>
    );
}
