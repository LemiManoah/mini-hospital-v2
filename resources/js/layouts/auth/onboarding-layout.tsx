import AppLogoIcon from '@/components/app-logo-icon';
import { ImpersonationBanner } from '@/components/impersonation-banner';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { CheckCircle2, CircleDotDashed } from 'lucide-react';
import { type PropsWithChildren } from 'react';

type Step = {
    key: string;
    title: string;
    description: string;
    status: 'complete' | 'current' | 'upcoming';
};

interface OnboardingLayoutProps {
    title: string;
    description: string;
    tenantName: string;
    steps: Step[];
    currentStep: string;
    asideNote?: string;
    sidebarLabel?: string;
}

function stepAccent(status: Step['status']): string {
    return (
        {
            complete: 'border-emerald-200 bg-emerald-50 text-emerald-900',
            current: 'border-white/20 bg-white/10 text-white',
            upcoming: 'border-white/10 bg-white/5 text-zinc-300',
        }[status] ?? 'border-white/10 bg-white/5 text-zinc-300'
    );
}

export default function OnboardingLayout({
    children,
    title,
    description,
    tenantName,
    steps,
    currentStep,
    asideNote,
    sidebarLabel = 'Active workspace',
}: PropsWithChildren<OnboardingLayoutProps>) {
    const { name } = usePage<SharedData>().props;

    return (
        <div className="min-h-dvh bg-zinc-100">
            <ImpersonationBanner />
            <div className="lg:grid lg:grid-cols-[440px_minmax(0,1fr)]">
                <aside className="relative hidden overflow-hidden border-r border-white/10 bg-zinc-950 text-white lg:flex">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.2),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.18),_transparent_28%)]" />
                    <div className="relative flex w-full flex-col px-10 py-12">
                        <Link
                            href="/"
                            className="flex items-center gap-3 text-sm font-medium text-white/90"
                        >
                            <AppLogoIcon className="size-9 fill-current text-white" />
                            <span>{name}</span>
                        </Link>

                        <div className="mt-12 space-y-5">
                            <Badge className="w-fit border-0 bg-white/10 text-white shadow-none">
                                Onboarding
                            </Badge>
                            <div className="space-y-3">
                                <p className="text-sm tracking-[0.22em] text-zinc-400 uppercase">
                                    {sidebarLabel}
                                </p>
                                <h1 className="text-3xl font-semibold tracking-tight">
                                    {tenantName}
                                </h1>
                                <p className="max-w-sm text-sm leading-6 text-zinc-300">
                                    Complete the essentials step by step so the
                                    workspace is clean, usable, and ready for
                                    the clinical team.
                                </p>
                            </div>
                        </div>

                        <div className="mt-10 space-y-3">
                            {steps.map((step, index) => (
                                <div
                                    key={step.key}
                                    className={cn(
                                        'rounded-2xl border p-4 transition-colors',
                                        stepAccent(step.status),
                                    )}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="mt-0.5">
                                            {step.status === 'complete' ? (
                                                <CheckCircle2 className="size-5" />
                                            ) : (
                                                <CircleDotDashed className="size-5" />
                                            )}
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-xs tracking-[0.2em] uppercase opacity-70">
                                                Step {index + 1}
                                            </p>
                                            <p className="font-medium">
                                                {step.title}
                                            </p>
                                            <p className="text-sm leading-6 opacity-80">
                                                {step.description}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <Card className="mt-auto border-white/10 bg-white/5 text-white shadow-none">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Current stage
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm text-zinc-300">
                                <p className="font-medium text-white">
                                    {
                                        steps.find(
                                            (step) => step.key === currentStep,
                                        )?.title
                                    }
                                </p>
                                <p>
                                    {asideNote ??
                                        'You can complete this process in a few short screens and continue where you left off.'}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </aside>

                <main className="flex min-h-dvh flex-col justify-center px-4 py-8 sm:px-6 lg:px-12">
                    <div className="mx-auto w-full max-w-3xl">
                        <div className="mb-8 space-y-3 lg:hidden">
                            <Link
                                href="/"
                                className="inline-flex items-center gap-3 text-sm font-medium text-zinc-700"
                            >
                                <AppLogoIcon className="size-8 fill-current text-zinc-950" />
                                <span>{name}</span>
                            </Link>
                            <div className="space-y-2">
                                <p className="text-sm tracking-[0.22em] text-zinc-500 uppercase">
                                    Onboarding
                                </p>
                                <h1 className="text-2xl font-semibold tracking-tight text-zinc-950">
                                    {tenantName}
                                </h1>
                            </div>
                        </div>

                        <div className="mb-8 space-y-2">
                            <h2 className="text-2xl font-semibold tracking-tight text-zinc-950 sm:text-3xl">
                                {title}
                            </h2>
                            <p className="max-w-2xl text-sm leading-6 text-zinc-600">
                                {description}
                            </p>
                        </div>

                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
