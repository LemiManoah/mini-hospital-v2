import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CircleCheckBig,
    CircleDashed,
    CreditCard,
    Globe,
    Users,
} from 'lucide-react';

interface Tenant {
    id: string;
    name: string;
    domain: string;
    onboarding_completed_at: string | null;
    country?: {
        country_name?: string;
        emoji?: string;
    } | null;
    current_subscription?: {
        status: string;
        status_label: string;
        package?: {
            name: string;
        } | null;
    } | null;
    counts: {
        branches: number;
        departments: number;
        staff: number;
    };
}

interface Props {
    tenants: Tenant[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'SaaS Admin',
        href: '/facility-switcher',
    },
];

export default function FacilitySwitcher({ tenants }: Props) {
    const { hasPermission } = usePermissions();
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="SaaS Admin" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Card className="rounded-3xl">
                    <CardHeader className="gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div className="space-y-2">
                            <Badge variant="secondary">
                                SaaS admin operations
                            </Badge>
                            <div className="space-y-1">
                                <CardTitle className="text-3xl tracking-tight">
                                    Tenant lifecycle dashboard
                                </CardTitle>
                                <CardDescription className="max-w-3xl text-sm leading-6">
                                    Review workspace readiness, onboarding
                                    state, subscription state, and jump into a
                                    tenant when support needs to intervene.
                                </CardDescription>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div className="rounded-2xl bg-muted/50 p-4">
                                <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                    Tenants
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {tenants.length}
                                </p>
                            </div>
                            <div className="rounded-2xl bg-muted/50 p-4">
                                <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                    Onboarded
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {
                                        tenants.filter(
                                            (tenant) =>
                                                tenant.onboarding_completed_at,
                                        ).length
                                    }
                                </p>
                            </div>
                            <div className="rounded-2xl bg-muted/50 p-4">
                                <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                    Active subs
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {
                                        tenants.filter(
                                            (tenant) =>
                                                tenant.current_subscription
                                                    ?.status === 'active',
                                        ).length
                                    }
                                </p>
                            </div>
                            <div className="rounded-2xl bg-muted/50 p-4">
                                <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                    Needs follow-up
                                </p>
                                <p className="mt-2 text-2xl font-semibold">
                                    {
                                        tenants.filter(
                                            (tenant) =>
                                                !tenant.onboarding_completed_at ||
                                                tenant.current_subscription
                                                    ?.status === 'past_due',
                                        ).length
                                    }
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <div className="grid gap-5 xl:grid-cols-2">
                    {tenants.map((tenant) => {
                        const onboardingComplete =
                            tenant.onboarding_completed_at !== null;

                        return (
                            <Card
                                key={tenant.id}
                                className="flex h-full flex-col rounded-3xl"
                            >
                                <CardHeader className="space-y-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                <Building2 className="h-6 w-6" />
                                            </div>
                                            <div>
                                                <CardTitle className="text-xl">
                                                    {tenant.name}
                                                </CardTitle>
                                                <CardDescription className="mt-1">
                                                    {tenant.domain}
                                                    .mini-hospital.com
                                                </CardDescription>
                                            </div>
                                        </div>
                                        <Badge
                                            variant={
                                                onboardingComplete
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                        >
                                            {onboardingComplete
                                                ? 'Onboarded'
                                                : 'Onboarding open'}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Globe className="h-4 w-4" />
                                            <span>
                                                {tenant.country?.emoji
                                                    ? `${tenant.country.emoji} `
                                                    : ''}
                                                {tenant.country?.country_name ??
                                                    'Country not set'}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <CreditCard className="h-4 w-4" />
                                            <span>
                                                {tenant.current_subscription
                                                    ?.status_label ??
                                                    'No subscription'}
                                            </span>
                                        </div>
                                    </div>
                                </CardHeader>

                                <CardContent className="mt-auto space-y-5">
                                    <div className="grid gap-3 sm:grid-cols-3">
                                        <div className="rounded-2xl bg-muted/50 p-4">
                                            <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                                Branches
                                            </p>
                                            <p className="mt-2 text-lg font-semibold">
                                                {tenant.counts.branches}
                                            </p>
                                        </div>
                                        <div className="rounded-2xl bg-muted/50 p-4">
                                            <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                                Departments
                                            </p>
                                            <p className="mt-2 text-lg font-semibold">
                                                {tenant.counts.departments}
                                            </p>
                                        </div>
                                        <div className="rounded-2xl bg-muted/50 p-4">
                                            <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                                Staff
                                            </p>
                                            <p className="mt-2 text-lg font-semibold">
                                                {tenant.counts.staff}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="grid gap-3 lg:grid-cols-2">
                                        <div className="rounded-2xl border p-4">
                                            <div className="flex items-center gap-2 text-sm font-medium">
                                                {onboardingComplete ? (
                                                    <CircleCheckBig className="h-4 w-4 text-emerald-600" />
                                                ) : (
                                                    <CircleDashed className="h-4 w-4 text-amber-600" />
                                                )}
                                                Onboarding
                                            </div>
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                {onboardingComplete
                                                    ? 'Tenant has completed onboarding.'
                                                    : 'Tenant still needs onboarding support.'}
                                            </p>
                                        </div>
                                        <div className="rounded-2xl border p-4">
                                            <div className="flex items-center gap-2 text-sm font-medium">
                                                <Users className="h-4 w-4 text-primary" />
                                                Subscription
                                            </div>
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                {tenant.current_subscription
                                                    ?.package?.name ??
                                                    'No package assigned'}{' '}
                                                with status{' '}
                                                {tenant.current_subscription
                                                    ?.status_label ??
                                                    'Unavailable'}
                                                .
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex flex-col gap-3 sm:flex-row">
                                        <Button asChild className="w-full">
                                            <Link
                                                href={`/facility-switcher/${tenant.id}`}
                                            >
                                                View tenant details
                                                <ArrowRight className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        {hasPermission('tenants.update') ? (
                                            <Button
                                                asChild
                                                variant="outline"
                                                className="w-full"
                                            >
                                                <Link
                                                    href={`/facility-switcher/${tenant.id}`}
                                                >
                                                    Open support actions
                                                </Link>
                                            </Button>
                                        ) : null}
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {tenants.length === 0 ? (
                    <Card className="rounded-3xl border-dashed">
                        <CardContent className="py-16 text-center">
                            <Building2 className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <p className="text-lg font-medium">
                                No tenants found
                            </p>
                        </CardContent>
                    </Card>
                ) : null}
            </div>
        </AppLayout>
    );
}
