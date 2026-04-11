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
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    CalendarClock,
    CreditCard,
    FlaskConical,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { type ReactNode } from 'react';

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
    } | null;
    current_subscription?: {
        id: string;
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
        users: number;
    };
    branches: Array<{
        id: string;
        name: string;
        branch_code: string | null;
        status: string | null;
    }>;
    departments: Array<{
        id: string;
        name: string;
    }>;
}

interface RecentUser {
    id: string;
    name: string;
    email: string;
    position?: string | null;
    roles: string[];
    email_verified_at: string | null;
    created_at: string | null;
}

interface SubscriptionHistoryItem {
    id: string;
    status: string;
    status_label: string;
    package?: string | null;
    trial_ends_at: string | null;
    activated_at: string | null;
    current_period_ends_at: string | null;
    created_at: string | null;
}

interface UsageSummary {
    patients: number;
    visits: number;
    lab_requests: number;
    prescriptions: number;
    verified_users: number;
    last_visit_at: string | null;
    last_lab_request_at: string | null;
    last_prescription_at: string | null;
}

interface FacilityManagerShowProps {
    tenant: TenantDetail;
    recent_users: RecentUser[];
    subscription_history: SubscriptionHistoryItem[];
    usage: UsageSummary;
}

const formatDate = (value: string | null): string =>
    value
        ? new Date(value).toLocaleDateString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
          })
        : 'Not set';

export default function FacilityManagerShow({
    tenant,
    recent_users,
    subscription_history,
    usage,
}: FacilityManagerShowProps) {
    const { hasPermission } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        { title: 'Facilities', href: '/facility-manager/facilities' },
        { title: tenant.name, href: `/facility-manager/facilities/${tenant.id}` },
    ];

    const onboardingComplete = tenant.onboarding_completed_at !== null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tenant.name} Facility Manager`} />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link href="/facility-manager/facilities">
                            <ArrowLeft className="h-4 w-4" />
                            Back to Facilities
                        </Link>
                    </Button>
                </div>

                <Card className="border-none shadow-sm ring-1 ring-border/50">
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
                                        ? 'Onboarding Complete'
                                        : 'Onboarding Open'}
                                </Badge>
                                <Badge variant="outline">
                                    {tenant.current_subscription?.status_label ??
                                        'No subscription'}
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
                                    {tenant.country?.country_name ?? 'Not set'}
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

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <UsageCard
                        title="Users"
                        value={tenant.counts.users}
                        hint={`${usage.verified_users} verified`}
                        icon={<Users className="h-4 w-4" />}
                    />
                    <UsageCard
                        title="Patients"
                        value={usage.patients}
                        hint="Registered patient records"
                        icon={<Users className="h-4 w-4" />}
                    />
                    <UsageCard
                        title="Visits"
                        value={usage.visits}
                        hint={`Last visit ${formatDate(usage.last_visit_at)}`}
                        icon={<CalendarClock className="h-4 w-4" />}
                    />
                    <UsageCard
                        title="Lab / Pharmacy"
                        value={usage.lab_requests + usage.prescriptions}
                        hint={`${usage.lab_requests} lab, ${usage.prescriptions} prescriptions`}
                        icon={<FlaskConical className="h-4 w-4" />}
                    />
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div className="space-y-6">
                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>Facility Structure</CardTitle>
                                <CardDescription>
                                    Branches and departments configured inside
                                    the tenant workspace.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2">
                                <div className="rounded-2xl border p-4">
                                    <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        Branches
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {tenant.counts.branches}
                                    </p>
                                    <div className="mt-3 space-y-2 text-sm text-muted-foreground">
                                        {tenant.branches.length > 0 ? (
                                            tenant.branches.map((branch) => (
                                                <div key={branch.id}>
                                                    {branch.branch_code
                                                        ? `${branch.name} (${branch.branch_code})`
                                                        : branch.name}
                                                </div>
                                            ))
                                        ) : (
                                            <div>No branches configured.</div>
                                        )}
                                    </div>
                                </div>
                                <div className="rounded-2xl border p-4">
                                    <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        Departments
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold">
                                        {tenant.counts.departments}
                                    </p>
                                    <div className="mt-3 space-y-2 text-sm text-muted-foreground">
                                        {tenant.departments.length > 0 ? (
                                            tenant.departments.map(
                                                (department) => (
                                                    <div key={department.id}>
                                                        {department.name}
                                                    </div>
                                                ),
                                            )
                                        ) : (
                                            <div>No departments configured.</div>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>Recent Users</CardTitle>
                                <CardDescription>
                                    Latest tenant-linked users for support and
                                    onboarding follow-up.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {recent_users.length > 0 ? (
                                    recent_users.map((user) => (
                                        <div
                                            key={user.id}
                                            className="flex flex-col gap-2 rounded-2xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {user.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {user.email}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Added {formatDate(user.created_at)}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {user.position ? (
                                                    <Badge variant="outline">
                                                        {user.position}
                                                    </Badge>
                                                ) : null}
                                                {user.roles.slice(0, 2).map((role) => (
                                                    <Badge
                                                        key={role}
                                                        variant="secondary"
                                                    >
                                                        {role}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No users have been created for this
                                        tenant yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>Subscription State</CardTitle>
                                <CardDescription>
                                    Current package information and renewal
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
                                    <StatBox
                                        title="Status"
                                        value={
                                            tenant.current_subscription
                                                ?.status_label ??
                                            'No subscription'
                                        }
                                    />
                                    <StatBox
                                        title="Trial Ends"
                                        value={formatDate(
                                            tenant.current_subscription
                                                ?.trial_ends_at ?? null,
                                        )}
                                    />
                                    <StatBox
                                        title="Activated"
                                        value={formatDate(
                                            tenant.current_subscription
                                                ?.activated_at ?? null,
                                        )}
                                    />
                                    <StatBox
                                        title="Current Period Ends"
                                        value={formatDate(
                                            tenant.current_subscription
                                                ?.current_period_ends_at ??
                                                null,
                                        )}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>Subscription History</CardTitle>
                                <CardDescription>
                                    Recent subscription records and transitions.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {subscription_history.length > 0 ? (
                                    subscription_history.map((subscription) => (
                                        <div
                                            key={subscription.id}
                                            className="rounded-2xl border p-4"
                                        >
                                            <div className="flex items-center justify-between gap-3">
                                                <Badge variant="outline">
                                                    {
                                                        subscription.status_label
                                                    }
                                                </Badge>
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDate(
                                                        subscription.created_at,
                                                    )}
                                                </span>
                                            </div>
                                            <p className="mt-2 font-medium">
                                                {subscription.package ??
                                                    'No package'}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Trial ends{' '}
                                                {formatDate(
                                                    subscription.trial_ends_at,
                                                )}
                                            </p>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No subscription history is available
                                        yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {hasPermission('tenants.update') ? (
                            <Card className="border-none shadow-sm ring-1 ring-border/50">
                                <CardHeader>
                                    <CardTitle>Support Actions</CardTitle>
                                    <CardDescription>
                                        Lifecycle and intervention controls for
                                        the tenant.
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
                                                Switch Into Tenant
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
                                                Activate Subscription
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
                                                Mark Subscription Past Due
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
                                                    Reopen Onboarding
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
                                                    Mark Onboarding Complete
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </CardContent>
                            </Card>
                        ) : null}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function UsageCard({
    title,
    value,
    hint,
    icon,
}: {
    title: string;
    value: number;
    hint: string;
    icon: ReactNode;
}) {
    return (
        <Card className="border-none shadow-sm ring-1 ring-border/50">
            <CardHeader className="space-y-0 pb-2">
                <CardDescription className="text-xs font-medium tracking-wider text-primary uppercase">
                    {title}
                </CardDescription>
                <CardTitle className="text-3xl font-bold text-primary">
                    {value}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    {icon}
                    <span>{hint}</span>
                </div>
            </CardContent>
        </Card>
    );
}

function StatBox({ title, value }: { title: string; value: string }) {
    return (
        <div className="rounded-2xl border p-4">
            <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                {title}
            </p>
            <p className="mt-2 font-medium">{value}</p>
        </div>
    );
}
