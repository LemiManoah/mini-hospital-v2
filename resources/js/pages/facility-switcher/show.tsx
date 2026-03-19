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
import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    CreditCard,
    Globe,
    RefreshCcw,
    ShieldCheck,
    Users,
} from 'lucide-react';

interface TenantDetail {
    id: string;
    name: string;
    domain: string;
    status: string | null;
    facility_level: string | null;
    onboarding_completed_at: string | null;
    address?: {
        display_name: string;
    } | null;
    country?: {
        country_name?: string;
        emoji?: string;
    } | null;
    current_subscription?: {
        status: string;
        status_label: string;
        trial_ends_at: string | null;
        activated_at: string | null;
        current_period_ends_at: string | null;
        package?: {
            name: string;
            price: string;
        } | null;
    } | null;
    counts: {
        branches: number;
        departments: number;
        staff: number;
    };
    branches: Array<{
        id: string;
        name: string;
        branch_code: string | null;
    }>;
    departments: Array<{
        id: string;
        name: string;
    }>;
    staff: Array<{
        id: string;
        name: string;
        email: string;
        position?: string | null;
    }>;
}

interface Props {
    tenant: TenantDetail;
}

function formatDate(value: string | null): string {
    if (!value) return 'Not set';

    return new Date(value).toLocaleDateString('en-UG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function FacilitySwitcherShow({ tenant }: Props) {
    const { hasPermission } = usePermissions();
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'SaaS Admin',
            href: '/facility-switcher',
        },
        {
            title: tenant.name,
            href: `/facility-switcher/${tenant.id}`,
        },
    ];

    const onboardingComplete = tenant.onboarding_completed_at !== null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tenant.name} Support`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link href="/facility-switcher">
                            <ArrowLeft className="h-4 w-4" />
                            Back to SaaS Admin
                        </Link>
                    </Button>
                </div>

                <Card className="rounded-3xl">
                    <CardHeader className="gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div className="space-y-4">
                            <div className="flex items-center gap-3">
                                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                    <Building2 className="h-7 w-7" />
                                </div>
                                <div>
                                    <CardTitle className="text-3xl tracking-tight">
                                        {tenant.name}
                                    </CardTitle>
                                    <CardDescription className="mt-1">
                                        {tenant.domain}.mini-hospital.com
                                    </CardDescription>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <Badge
                                    variant={
                                        onboardingComplete
                                            ? 'secondary'
                                            : 'outline'
                                    }
                                >
                                    {onboardingComplete
                                        ? 'Onboarding complete'
                                        : 'Onboarding open'}
                                </Badge>
                                <Badge variant="outline">
                                    {tenant.current_subscription
                                        ?.status_label ?? 'No subscription'}
                                </Badge>
                                {tenant.status ? (
                                    <Badge variant="outline">
                                        {tenant.status}
                                    </Badge>
                                ) : null}
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="rounded-2xl bg-muted/50 p-4">
                                <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                    Country
                                </p>
                                <p className="mt-2 font-medium">
                                    {tenant.country?.emoji
                                        ? `${tenant.country.emoji} `
                                        : ''}
                                    {tenant.country?.country_name ??
                                        'Not selected'}
                                </p>
                            </div>
                            <div className="rounded-2xl bg-muted/50 p-4">
                                <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                    Address
                                </p>
                                <p className="mt-2 font-medium">
                                    {tenant.address?.display_name ?? 'Not set'}
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div className="space-y-6">
                        <Card className="rounded-3xl">
                            <CardHeader>
                                <CardTitle>Workspace readiness</CardTitle>
                                <CardDescription>
                                    Quick operational visibility for support.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-3">
                                <div className="rounded-2xl border p-4">
                                    <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        Branches
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {tenant.counts.branches}
                                    </p>
                                </div>
                                <div className="rounded-2xl border p-4">
                                    <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        Departments
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {tenant.counts.departments}
                                    </p>
                                </div>
                                <div className="rounded-2xl border p-4">
                                    <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        Staff
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {tenant.counts.staff}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-3xl">
                            <CardHeader>
                                <CardTitle>Onboarding status</CardTitle>
                                <CardDescription>
                                    See whether the tenant has finished the
                                    guided setup flow.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="rounded-2xl border p-4">
                                    <div className="flex items-center gap-2 text-sm font-medium">
                                        {onboardingComplete ? (
                                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                        ) : (
                                            <RefreshCcw className="h-4 w-4 text-amber-600" />
                                        )}
                                        Current state
                                    </div>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {onboardingComplete
                                            ? `Completed on ${formatDate(tenant.onboarding_completed_at)}.`
                                            : 'The tenant can still be redirected back into onboarding.'}
                                    </p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="rounded-2xl border p-4">
                                        <p className="text-sm font-medium">
                                            Branches configured
                                        </p>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {tenant.branches.length > 0
                                                ? tenant.branches
                                                      .map((branch) =>
                                                          branch.branch_code
                                                              ? `${branch.name} (${branch.branch_code})`
                                                              : branch.name,
                                                      )
                                                      .join(', ')
                                                : 'No branches yet'}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border p-4">
                                        <p className="text-sm font-medium">
                                            Departments configured
                                        </p>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {tenant.departments.length > 0
                                                ? tenant.departments
                                                      .map(
                                                          (department) =>
                                                              department.name,
                                                      )
                                                      .join(', ')
                                                : 'No departments yet'}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-3xl">
                            <CardHeader>
                                <CardTitle>Tenant staff</CardTitle>
                                <CardDescription>
                                    First staff records created during
                                    onboarding and later setup.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {tenant.staff.length > 0 ? (
                                    tenant.staff.map((staffMember) => (
                                        <div
                                            key={staffMember.id}
                                            className="flex flex-col gap-1 rounded-2xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {staffMember.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {staffMember.email}
                                                </p>
                                            </div>
                                            <Badge variant="outline">
                                                {staffMember.position ??
                                                    'No position'}
                                            </Badge>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No staff records yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card className="rounded-3xl">
                            <CardHeader>
                                <CardTitle>Subscription state</CardTitle>
                                <CardDescription>
                                    Current lifecycle details and renewal
                                    window.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="rounded-2xl border p-4">
                                    <div className="flex items-center gap-2 text-sm font-medium">
                                        <CreditCard className="h-4 w-4 text-primary" />
                                        Package
                                    </div>
                                    <p className="mt-2">
                                        {tenant.current_subscription?.package
                                            ?.name ?? 'Not set'}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {tenant.current_subscription?.package
                                            ?.price ?? 'No price available'}
                                    </p>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="rounded-2xl border p-4">
                                        <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                            Status
                                        </p>
                                        <p className="mt-2 font-medium">
                                            {tenant.current_subscription
                                                ?.status_label ??
                                                'No subscription'}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border p-4">
                                        <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                            Trial ends
                                        </p>
                                        <p className="mt-2 font-medium">
                                            {formatDate(
                                                tenant.current_subscription
                                                    ?.trial_ends_at ?? null,
                                            )}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border p-4">
                                        <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                            Activated
                                        </p>
                                        <p className="mt-2 font-medium">
                                            {formatDate(
                                                tenant.current_subscription
                                                    ?.activated_at ?? null,
                                            )}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border p-4">
                                        <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                            Current period ends
                                        </p>
                                        <p className="mt-2 font-medium">
                                            {formatDate(
                                                tenant.current_subscription
                                                    ?.current_period_ends_at ??
                                                    null,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {hasPermission('tenants.update') ? (
                            <Card className="rounded-3xl">
                                <CardHeader>
                                    <CardTitle>Support actions</CardTitle>
                                    <CardDescription>
                                        Controlled lifecycle operations for
                                        tenant activation and recovery.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <Form
                                        method="post"
                                        action={`/facility-switcher/${tenant.id}`}
                                        className="w-full"
                                    >
                                        {() => (
                                            <Button className="w-full">
                                                <ShieldCheck className="h-4 w-4" />
                                                Switch into tenant
                                            </Button>
                                        )}
                                    </Form>

                                    <Form
                                        method="post"
                                        action={`/facility-switcher/${tenant.id}/activate-subscription`}
                                        className="w-full"
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="w-full"
                                            >
                                                Activate subscription
                                            </Button>
                                        )}
                                    </Form>

                                    <Form
                                        method="post"
                                        action={`/facility-switcher/${tenant.id}/mark-subscription-past-due`}
                                        className="w-full"
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                variant="outline"
                                                className="w-full"
                                            >
                                                Mark subscription past due
                                            </Button>
                                        )}
                                    </Form>

                                    {onboardingComplete ? (
                                        <Form
                                            method="post"
                                            action={`/facility-switcher/${tenant.id}/reopen-onboarding`}
                                            className="w-full"
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                    variant="outline"
                                                    className="w-full"
                                                >
                                                    Reopen onboarding
                                                </Button>
                                            )}
                                        </Form>
                                    ) : (
                                        <Form
                                            method="post"
                                            action={`/facility-switcher/${tenant.id}/complete-onboarding`}
                                            className="w-full"
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                    variant="secondary"
                                                    className="w-full"
                                                >
                                                    Mark onboarding complete
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </CardContent>
                            </Card>
                        ) : null}

                        <Card className="rounded-3xl">
                            <CardHeader>
                                <CardTitle>Tenant location</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm text-muted-foreground">
                                <div className="flex items-center gap-2">
                                    <Globe className="h-4 w-4" />
                                    <span>
                                        {tenant.country?.emoji
                                            ? `${tenant.country.emoji} `
                                            : ''}
                                        {tenant.country?.country_name ??
                                            'Country not set'}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Users className="h-4 w-4" />
                                    <span>
                                        {tenant.address?.display_name ??
                                            'Address not set'}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
