import { dashboard } from '@/routes';
import { Link } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    Blocks,
    ChartNoAxesCombined,
    ClipboardPlus,
    FileClock,
    FlaskConical,
    HeartPulse,
    Pill,
    ShieldCheck,
    Stethoscope,
} from 'lucide-react';
import { type HeaderFact } from './types';

const queueMetrics = [
    { label: 'Waiting Triage', value: '18', icon: Activity },
    { label: 'Doctor Queue', value: '11', icon: Stethoscope },
    { label: 'Pending Results', value: '6', icon: FlaskConical },
];

const patientFlow = [
    'Patient registered at front desk',
    'Vitals captured in triage',
    'Orders placed during consultation',
    'Result ready for release',
];

const moduleStatus = [
    { icon: ClipboardPlus, label: 'Visits', status: 'Live' },
    { icon: Stethoscope, label: 'Consultation', status: 'Live' },
    { icon: FlaskConical, label: 'Laboratory', status: 'Queued' },
    { icon: Pill, label: 'Pharmacy', status: 'Ready' },
    { icon: FileClock, label: 'Billing', status: 'Linked' },
];

export function WelcomeHeroSection({
    headerFacts,
    authenticated,
}: {
    headerFacts: HeaderFact[];
    authenticated: boolean;
}) {
    return (
        <section
            id="home"
            className="relative overflow-hidden border-b border-stone-200 px-6 pt-20 pb-12 dark:border-white/6 md:px-10 md:pt-24"
        >
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_18%,rgba(74,111,165,0.12),transparent_34%),radial-gradient(circle_at_84%_78%,rgba(74,111,165,0.09),transparent_28%)] dark:bg-[radial-gradient(circle_at_50%_18%,rgba(74,111,165,0.16),transparent_34%),radial-gradient(circle_at_84%_78%,rgba(74,111,165,0.08),transparent_28%)]" />

            <div className="relative mx-auto flex w-full max-w-[1440px] flex-col gap-12">
                <div className="grid gap-8 border-b border-stone-200 pb-8 dark:border-white/8 md:grid-cols-4">
                    {headerFacts.map((fact) => (
                        <div key={fact.label} className="flex flex-col gap-1">
                            <span className="text-[10px] font-semibold tracking-[0.32em] text-stone-500 uppercase dark:text-stone-600">
                                {fact.label}
                            </span>
                            <span className="text-xs font-semibold text-stone-900 dark:text-stone-100">
                                {fact.value}
                            </span>
                        </div>
                    ))}
                </div>

                <div className="max-w-6xl">
                    <h1 className="font-[Manrope] text-[3rem] leading-[0.86] font-extrabold tracking-[-0.08em] text-stone-950 dark:text-stone-100 sm:text-[4.4rem] lg:text-[7.4rem]">
                        CLINICAL
                        <br />
                        FLOW MADE
                        <br />
                        SIMPLE
                    </h1>
                </div>

                <div className="grid gap-8 xl:grid-cols-[minmax(0,1.08fr)_minmax(320px,0.92fr)] xl:items-end">
                    <div className="order-2 border-l border-[#4A6FA5]/30 pl-6 xl:order-1 xl:pl-8">
                        <span className="mb-4 block font-[Manrope] text-[10px] font-bold tracking-[0.38em] text-[#4A6FA5] uppercase dark:text-[#7ea2d6]">
                            About QrooEMR
                        </span>
                        <p className="max-w-sm text-sm leading-7 text-stone-600 dark:text-stone-400">
                            QrooEMR is built for hospitals that want cleaner patient movement, calmer documentation, and operational visibility across registration, consultation, laboratory, pharmacy, and billing.
                        </p>

                        <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <div className="border border-stone-200 bg-white/80 p-4 shadow-[0_12px_30px_rgba(15,23,42,0.05)] dark:border-white/8 dark:bg-[#141515] dark:shadow-none">
                                <div className="flex items-center justify-between">
                                    <span className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase dark:text-stone-500">
                                        Product Character
                                    </span>
                                    <ShieldCheck className="h-4 w-4 text-[#4A6FA5] dark:text-[#7ea2d6]" />
                                </div>
                                <p className="mt-4 font-[Manrope] text-2xl font-bold tracking-[-0.05em] text-stone-950 dark:text-stone-100">
                                    Structured. Calm. Operational.
                                </p>
                                <p className="mt-3 text-sm leading-7 text-stone-600 dark:text-stone-400">
                                    Designed for facilities that want clarity on screen without clutter in the workflow.
                                </p>
                            </div>

                            <div className="border border-stone-200 bg-white/80 p-4 shadow-[0_12px_30px_rgba(15,23,42,0.05)] dark:border-white/8 dark:bg-[#141515] dark:shadow-none">
                                <div className="flex items-center justify-between">
                                    <span className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase dark:text-stone-500">
                                        Clinical Reach
                                    </span>
                                    <HeartPulse className="h-4 w-4 text-[#4A6FA5] dark:text-[#7ea2d6]" />
                                </div>
                                <p className="mt-4 text-sm font-medium leading-6 text-stone-700 dark:text-stone-300">
                                    Front desk, triage, consultation, laboratory, pharmacy, and reporting working in one flow.
                                </p>
                            </div>
                        </div>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row xl:flex-col 2xl:flex-row">
                            {authenticated ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center justify-center bg-[#4A6FA5] px-6 py-3 font-[Manrope] text-[10px] font-bold tracking-[0.3em] text-white uppercase transition hover:bg-[#5f84bb]"
                                >
                                    Open Dashboard
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href="/create-workspace"
                                        className="inline-flex items-center justify-center bg-[#4A6FA5] px-6 py-3 font-[Manrope] text-[10px] font-bold tracking-[0.3em] text-white uppercase transition hover:bg-[#5f84bb]"
                                    >
                                        Launch Workspace
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Link>
                                    <a
                                        href="mailto:sales@qroo.rw?subject=QrooEMR%20Demo%20Request"
                                        className="inline-flex items-center justify-center border border-stone-300 px-6 py-3 font-[Manrope] text-[10px] font-bold tracking-[0.3em] text-stone-900 uppercase transition hover:border-[#4A6FA5] hover:text-[#4A6FA5] dark:border-white/10 dark:text-stone-200 dark:hover:border-[#4A6FA5] dark:hover:text-[#9bb5da]"
                                    >
                                        Request Demo
                                    </a>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="order-1 xl:order-2">
                        <div className="overflow-hidden border border-stone-200 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.08)] dark:border-white/8 dark:bg-[#131313] dark:shadow-none">
                            <div className="border-b border-stone-200 px-5 py-4 dark:border-white/8 sm:px-6">
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <p className="text-[10px] font-bold tracking-[0.32em] text-stone-500 uppercase dark:text-stone-500">
                                            Live Workspace
                                        </p>
                                        <p className="mt-3 font-[Manrope] text-2xl font-bold tracking-[-0.05em] text-stone-950 dark:text-stone-100">
                                            Morning shift operational canvas
                                        </p>
                                    </div>
                                    <div className="inline-flex items-center gap-2 rounded-full border border-[#4A6FA5]/20 bg-[#4A6FA5]/8 px-3 py-1.5 text-[10px] font-bold tracking-[0.24em] text-[#4A6FA5] uppercase dark:border-[#7ea2d6]/35 dark:bg-[#7ea2d6]/10 dark:text-[#9bb5da]">
                                        <ChartNoAxesCombined className="h-3.5 w-3.5" />
                                        Flow Snapshot
                                    </div>
                                </div>
                                <p className="mt-3 max-w-2xl text-sm leading-7 text-stone-600 dark:text-stone-400">
                                    A compact surface showing queue pressure, patient movement, and module readiness without looking overloaded on smaller screens.
                                </p>
                            </div>

                            <div className="grid gap-4 bg-stone-100 p-3 dark:bg-[#0b0b0b] sm:p-4 2xl:grid-cols-[minmax(0,1.08fr)_minmax(240px,0.92fr)]">
                                <div className="grid gap-4">
                                    <div className="border border-stone-200 bg-white p-4 dark:border-white/8 dark:bg-[#161717]">
                                        <div className="mb-4 flex items-center justify-between border-b border-stone-200 pb-4 dark:border-white/8">
                                            <div>
                                                <p className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase dark:text-stone-500">
                                                    Queue Pressure
                                                </p>
                                                <p className="mt-2 text-lg font-semibold text-stone-950 dark:text-stone-100">
                                                    Where teams should act next
                                                </p>
                                            </div>
                                            <Activity className="h-5 w-5 text-[#4A6FA5] dark:text-[#7ea2d6]" />
                                        </div>

                                        <div className="grid gap-3 sm:grid-cols-3">
                                            {queueMetrics.map((metric) => (
                                                <div
                                                    key={metric.label}
                                                    className="border border-stone-200 bg-stone-50 p-3 dark:border-white/8 dark:bg-[#101111]"
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-[10px] font-bold tracking-[0.24em] text-stone-500 uppercase dark:text-stone-500">
                                                            {metric.label}
                                                        </span>
                                                        <metric.icon className="h-4 w-4 text-[#4A6FA5] dark:text-[#7ea2d6]" />
                                                    </div>
                                                    <p className="mt-4 font-[Manrope] text-3xl font-bold tracking-[-0.05em] text-stone-950 dark:text-stone-100">
                                                        {metric.value}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="grid gap-4 2xl:grid-cols-2">
                                        <div className="border border-stone-200 bg-white p-4 dark:border-white/8 dark:bg-[#161717]">
                                            <div className="mb-4 flex items-center justify-between">
                                                <span className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase dark:text-stone-500">
                                                    Patient Flow
                                                </span>
                                                <HeartPulse className="h-4 w-4 text-[#4A6FA5] dark:text-[#7ea2d6]" />
                                            </div>
                                            <div className="space-y-3">
                                                {patientFlow.map((item) => (
                                                    <div key={item} className="flex items-start gap-3">
                                                        <span className="mt-1.5 size-2 rounded-full bg-[#4A6FA5]" />
                                                        <span className="text-sm leading-6 text-stone-700 dark:text-stone-300">
                                                            {item}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="border border-stone-200 bg-white p-4 dark:border-white/8 dark:bg-[#161717]">
                                            <div className="mb-4 flex items-center justify-between">
                                                <span className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase dark:text-stone-500">
                                                    Modules
                                                </span>
                                                <Blocks className="h-4 w-4 text-[#4A6FA5] dark:text-[#7ea2d6]" />
                                            </div>
                                            <div className="space-y-3">
                                                {moduleStatus.map((module) => (
                                                    <div
                                                        key={module.label}
                                                        className="flex items-center justify-between border-b border-stone-200 pb-2 last:border-none last:pb-0 dark:border-white/8"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <module.icon className="h-4 w-4 text-[#4A6FA5] dark:text-[#7ea2d6]" />
                                                            <span className="text-sm text-stone-700 dark:text-stone-300">
                                                                {module.label}
                                                            </span>
                                                        </div>
                                                        <span className="text-[10px] font-semibold tracking-[0.18em] text-stone-500 uppercase dark:text-stone-500">
                                                            {module.status}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* <div className="grid gap-4">
                                    <div className="border border-stone-200 bg-white p-4 dark:border-white/8 dark:bg-[#161717]">
                                        <div className="flex items-center justify-between">
                                            <span className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase dark:text-stone-500">
                                                Today
                                            </span>
                                            <ClipboardPlus className="h-4 w-4 text-[#4A6FA5] dark:text-[#7ea2d6]" />
                                        </div>
                                        <p className="mt-4 font-[Manrope] text-4xl font-bold tracking-[-0.06em] text-stone-950 dark:text-stone-100">
                                            42
                                        </p>
                                        <p className="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400">
                                            patient interactions currently visible across the workflow
                                        </p>
                                    </div>

                                   
                                </div> */}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
