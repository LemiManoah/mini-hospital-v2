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
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { FacilityManagerMetrics } from './components/facility-manager-metrics';
import { FacilityManagerNav } from './components/facility-manager-nav';
import { FacilityManagerTenantHeader } from './components/facility-manager-tenant-header';
import {
    type FacilityManagerMetric,
    type FacilityManagerTenantSummary,
} from './types';

interface OverviewTenant extends FacilityManagerTenantSummary {
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
    package?: {
        name: string;
        price?: string;
    } | null;
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
    support_notes: number;
    last_visit_at: string | null;
    last_lab_request_at: string | null;
    last_prescription_at: string | null;
    last_support_note_at: string | null;
}

interface FacilityManagerShowProps {
    tenant: OverviewTenant;
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
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        { title: 'Facilities', href: '/facility-manager/facilities' },
        {
            title: tenant.name,
            href: `/facility-manager/facilities/${tenant.id}`,
        },
    ];

    const metrics: FacilityManagerMetric[] = [
        {
            label: 'Users',
            value: tenant.counts.users,
            hint: `${usage.verified_users} verified accounts`,
        },
        {
            label: 'Patients',
            value: usage.patients,
            hint: 'Registered patient records',
        },
        {
            label: 'Visits',
            value: usage.visits,
            hint: `Last visit ${formatDate(usage.last_visit_at)}`,
        },
        {
            label: 'Lab / Pharmacy',
            value: usage.lab_requests + usage.prescriptions,
            hint: `${usage.lab_requests} lab requests, ${usage.prescriptions} prescriptions`,
        },
    ];

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

                <FacilityManagerTenantHeader
                    tenant={tenant}
                    title="Facility Overview"
                    description="Use the focused sections below to inspect branches, users, subscriptions, activity, and internal support notes."
                />

                <FacilityManagerNav tenantId={tenant.id} current="overview" />

                <FacilityManagerMetrics metrics={metrics} />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OverviewLinkCard
                        title="Branches"
                        description="Inspect branch status, staffing coverage, and store-enabled locations."
                        href={`/facility-manager/facilities/${tenant.id}/branches`}
                    />
                    <OverviewLinkCard
                        title="Users"
                        description="Review tenant-linked users, positions, and branch assignments."
                        href={`/facility-manager/facilities/${tenant.id}/users`}
                    />
                    <OverviewLinkCard
                        title="Subscriptions"
                        description="Track package status, billing windows, and support actions."
                        href={`/facility-manager/facilities/${tenant.id}/subscriptions`}
                    />
                    <OverviewLinkCard
                        title="Activity"
                        description="See operational volume and the latest facility events."
                        href={`/facility-manager/facilities/${tenant.id}/activity`}
                    />
                    <OverviewLinkCard
                        title="Support Notes"
                        description="Capture onboarding notes, billing context, and internal reminders."
                        href={`/facility-manager/facilities/${tenant.id}/support-notes`}
                    />
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div className="space-y-6">
                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>Facility Structure</CardTitle>
                                <CardDescription>
                                    Quick look at branch and department
                                    coverage.
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
                                            tenant.branches
                                                .slice(0, 5)
                                                .map((branch) => (
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
                                            tenant.departments
                                                .slice(0, 5)
                                                .map((department) => (
                                                    <div key={department.id}>
                                                        {department.name}
                                                    </div>
                                                ))
                                        ) : (
                                            <div>
                                                No departments configured.
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>Recent Users</CardTitle>
                                <CardDescription>
                                    Latest tenant-linked users for support
                                    follow-up.
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
                                                    Added{' '}
                                                    {formatDate(
                                                        user.created_at,
                                                    )}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {user.position ? (
                                                    <Badge variant="outline">
                                                        {user.position}
                                                    </Badge>
                                                ) : null}
                                                {user.roles
                                                    .slice(0, 2)
                                                    .map((role) => (
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
                                <CardTitle>Current Subscription</CardTitle>
                                <CardDescription>
                                    Present package state and renewal window.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="rounded-2xl border p-4">
                                    <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        Package
                                    </p>
                                    <p className="mt-2 font-medium">
                                        {tenant.current_subscription?.package
                                            ?.name ?? 'No package'}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {tenant.current_subscription
                                            ?.status_label ??
                                            'No active subscription'}
                                    </p>
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <StatBox
                                        title="Trial Ends"
                                        value={formatDate(
                                            tenant.current_subscription
                                                ?.trial_ends_at ?? null,
                                        )}
                                    />
                                    <StatBox
                                        title="Period Ends"
                                        value={formatDate(
                                            tenant.current_subscription
                                                ?.current_period_ends_at ??
                                                null,
                                        )}
                                    />
                                    <StatBox
                                        title="Support Notes"
                                        value={`${usage.support_notes}`}
                                    />
                                    <StatBox
                                        title="Last Note"
                                        value={formatDate(
                                            usage.last_support_note_at,
                                        )}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>
                                    Recent Subscription History
                                </CardTitle>
                                <CardDescription>
                                    Latest subscription transitions for quick
                                    context.
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
                                                    {subscription.status_label}
                                                </Badge>
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDate(
                                                        subscription.created_at,
                                                    )}
                                                </span>
                                            </div>
                                            <p className="mt-2 font-medium">
                                                {subscription.package?.name ??
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
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function OverviewLinkCard({
    title,
    description,
    href,
}: {
    title: string;
    description: string;
    href: string;
}) {
    return (
        <Card className="border-none shadow-sm ring-1 ring-border/50">
            <CardHeader className="space-y-3">
                <div className="flex items-center gap-2 text-sm font-medium text-primary">
                    {title}
                </div>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button asChild size="sm">
                    <Link href={href}>Open</Link>
                </Button>
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
