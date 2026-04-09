import { Activity, ArrowRight, Blocks, ChartNoAxesCombined, ClipboardPlus, FileClock, FlaskConical, HeartPulse, Pill, Stethoscope } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { dashboard } from '@/routes';
import { type HeaderFact } from './types';

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
            className="relative overflow-hidden border-b border-white/6 px-6 pt-20 pb-12 md:px-10 md:pt-24"
        >
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_18%,rgba(74,111,165,0.16),transparent_34%),radial-gradient(circle_at_84%_78%,rgba(74,111,165,0.08),transparent_28%)]" />

            <div className="relative mx-auto flex min-h-[calc(100vh-8rem)] w-full max-w-[1440px] flex-col justify-between gap-12">
                <div className="grid gap-8 border-b border-white/8 pb-8 md:grid-cols-4">
                    {headerFacts.map((fact) => (
                        <div key={fact.label} className="flex flex-col gap-1">
                            <span className="text-[10px] font-semibold tracking-[0.32em] text-stone-600 uppercase">
                                {fact.label}
                            </span>
                            <span className="text-xs font-semibold text-stone-100">
                                {fact.value}
                            </span>
                        </div>
                    ))}
                </div>

                <div className="max-w-6xl">
                    <h1 className="font-[Manrope] text-[3.2rem] leading-[0.84] font-extrabold tracking-[-0.08em] text-stone-100 sm:text-[4.8rem] lg:text-[7.8rem]">
                        CLINICAL
                        <br />
                        FLOW MADE
                        <br />
                        SIMPLE
                    </h1>
                </div>

                <div className="grid items-end gap-12 md:grid-cols-12">
                    <div className="border-l border-[#4A6FA5]/40 pl-6 md:col-span-4 md:pl-8">
                        <span className="mb-4 block font-[Manrope] text-[10px] font-bold tracking-[0.38em] text-[#7ea2d6] uppercase">
                            About QrooEMR
                        </span>
                        <p className="max-w-sm text-sm leading-7 text-stone-400">
                            QrooEMR is built for hospitals that want cleaner patient movement, calmer documentation, and operational visibility across registration, consultation, laboratory, pharmacy, and billing.
                        </p>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
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
                                        className="inline-flex items-center justify-center border border-white/10 px-6 py-3 font-[Manrope] text-[10px] font-bold tracking-[0.3em] text-stone-200 uppercase transition hover:border-[#4A6FA5] hover:text-[#9bb5da]"
                                    >
                                        Request Demo
                                    </a>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="md:col-span-8">
                        <div className="group relative overflow-hidden border border-white/8 bg-[#131313]">
                            <div className="aspect-[16/9] p-5 sm:p-8">
                                <div className="grid h-full gap-4 lg:grid-cols-[1.25fr_0.75fr]">
                                    <div className="flex flex-col gap-4 border border-white/6 bg-[#161717] p-5">
                                        <div className="flex items-center justify-between border-b border-white/6 pb-4">
                                            <div>
                                                <p className="font-[Manrope] text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase">
                                                    Live Workspace
                                                </p>
                                                <p className="mt-2 text-lg font-semibold text-stone-100">
                                                    Morning shift operational canvas
                                                </p>
                                            </div>
                                            <ChartNoAxesCombined className="h-5 w-5 text-[#7ea2d6]" />
                                        </div>

                                        <div className="grid flex-1 gap-4 md:grid-cols-2">
                                            <div className="border border-white/6 bg-black/20 p-4">
                                                <div className="mb-4 flex items-center justify-between">
                                                    <span className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase">
                                                        Queues
                                                    </span>
                                                    <Activity className="h-4 w-4 text-stone-500" />
                                                </div>
                                                <div className="space-y-3 text-sm">
                                                    <div className="flex items-center justify-between border-b border-white/6 pb-2">
                                                        <span className="text-stone-400">Waiting triage</span>
                                                        <span className="font-semibold text-stone-100">18</span>
                                                    </div>
                                                    <div className="flex items-center justify-between border-b border-white/6 pb-2">
                                                        <span className="text-stone-400">Consultation</span>
                                                        <span className="font-semibold text-stone-100">11</span>
                                                    </div>
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-stone-400">Results to review</span>
                                                        <span className="font-semibold text-stone-100">6</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="border border-white/6 bg-black/20 p-4">
                                                <div className="mb-4 flex items-center justify-between">
                                                    <span className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase">
                                                        Patient Flow
                                                    </span>
                                                    <HeartPulse className="h-4 w-4 text-stone-500" />
                                                </div>
                                                <div className="space-y-3">
                                                    {[
                                                        'Registration completed',
                                                        'Vitals captured',
                                                        'Doctor orders placed',
                                                        'Result released',
                                                    ].map((item) => (
                                                        <div key={item} className="flex items-center gap-3">
                                                            <span className="size-2 rounded-full bg-[#4A6FA5]" />
                                                            <span className="text-sm text-stone-300">{item}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="grid gap-4">
                                        <div className="border border-white/6 bg-[#161717] p-5">
                                            <div className="mb-4 flex items-center justify-between">
                                                <span className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase">
                                                    Modules
                                                </span>
                                                <Blocks className="h-4 w-4 text-stone-500" />
                                            </div>
                                            <div className="space-y-3">
                                                {[
                                                    { icon: ClipboardPlus, label: 'Visits' },
                                                    { icon: Stethoscope, label: 'Consultation' },
                                                    { icon: FlaskConical, label: 'Laboratory' },
                                                    { icon: Pill, label: 'Pharmacy' },
                                                    { icon: FileClock, label: 'Billing' },
                                                ].map((module) => (
                                                    <div
                                                        key={module.label}
                                                        className="flex items-center justify-between border-b border-white/6 pb-2 last:border-none last:pb-0"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <module.icon className="h-4 w-4 text-[#7ea2d6]" />
                                                            <span className="text-sm text-stone-300">
                                                                {module.label}
                                                            </span>
                                                        </div>
                                                        <span className="text-xs text-stone-500">Ready</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="border border-white/8 bg-[linear-gradient(180deg,rgba(74,111,165,0.12),rgba(0,0,0,0.1))] p-5">
                                            <p className="text-[10px] font-bold tracking-[0.3em] text-stone-500 uppercase">
                                                System Character
                                            </p>
                                            <p className="mt-3 font-[Manrope] text-2xl font-bold tracking-[-0.05em] text-stone-100">
                                                Structured. Calm. Operational.
                                            </p>
                                            <p className="mt-3 text-sm leading-7 text-stone-400">
                                                Designed for facilities that want clarity on screen without clutter in the workflow.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="absolute right-6 bottom-6 flex size-12 items-center justify-center rounded-full border border-[#4A6FA5]/40 bg-[#4A6FA5]/15 text-[#7ea2d6] backdrop-blur-sm transition duration-500 group-hover:bg-[#4A6FA5]/25">
                                <ArrowRight className="h-5 w-5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
