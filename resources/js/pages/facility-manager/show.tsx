import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { FacilityManagerMetrics } from './components/facility-manager-metrics';
import { FacilityManagerNav } from './components/facility-manager-nav';
import { FacilityManagerSupportActions } from './components/facility-manager-support-actions';
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

                <FacilityManagerTenantHeader tenant={tenant} />

                <FacilityManagerNav tenantId={tenant.id} current="overview" />

                <FacilityManagerMetrics metrics={metrics} />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                    <div className="min-w-0 space-y-6">
                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardContent className="space-y-6 p-6">
                                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div className="space-y-1">
                                        <h2 className="text-base font-semibold">
                                            Facility Snapshot
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            Key setup details for branches,
                                            departments, users, and support
                                            follow-up.
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/facility-manager/facilities/${tenant.id}/branches`}
                                            >
                                                Branches
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/facility-manager/facilities/${tenant.id}/users`}
                                            >
                                                Users
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/facility-manager/facilities/${tenant.id}/subscriptions`}
                                            >
                                                Subscriptions
                                            </Link>
                                        </Button>
                                    </div>
                                </div>

                                <div className="grid gap-4 lg:grid-cols-3">
                                    <div className="rounded-lg bg-muted/35 p-4">
                                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                            Branches
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {tenant.counts.branches}
                                        </p>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {tenant.branches.length > 0
                                                ? tenant.branches
                                                      .slice(0, 4)
                                                      .map((branch) =>
                                                          branch.branch_code
                                                              ? `${branch.name} (${branch.branch_code})`
                                                              : branch.name,
                                                      )
                                                      .join(', ')
                                                : 'No branches configured'}
                                        </p>
                                    </div>

                                    <div className="rounded-lg bg-muted/35 p-4">
                                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                            Departments
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {tenant.counts.departments}
                                        </p>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {tenant.departments.length > 0
                                                ? tenant.departments
                                                      .slice(0, 4)
                                                      .map(
                                                          (department) =>
                                                              department.name,
                                                      )
                                                      .join(', ')
                                                : 'No departments configured'}
                                        </p>
                                    </div>

                                    <div className="rounded-lg bg-muted/35 p-4">
                                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                            Users
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {tenant.counts.users}
                                        </p>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {usage.verified_users} verified
                                            accounts
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Last note{' '}
                                            {formatDate(
                                                usage.last_support_note_at,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardContent className="space-y-4 p-6">
                                <div className="space-y-1">
                                    <h2 className="text-base font-semibold">
                                        Recent Users
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Latest accounts created for this
                                        facility.
                                    </p>
                                </div>
                                {recent_users.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Name</TableHead>
                                                    <TableHead>Email</TableHead>
                                                    <TableHead>
                                                        Position
                                                    </TableHead>
                                                    <TableHead>Roles</TableHead>
                                                    <TableHead>Added</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {recent_users.map((user) => (
                                                    <TableRow key={user.id}>
                                                        <TableCell className="min-w-40 font-medium">
                                                            {user.name}
                                                        </TableCell>
                                                        <TableCell className="max-w-56 text-sm break-words text-muted-foreground">
                                                            {user.email}
                                                        </TableCell>
                                                        <TableCell className="min-w-32">
                                                            {user.position ??
                                                                '-'}
                                                        </TableCell>
                                                        <TableCell className="min-w-32">
                                                            <div className="flex flex-wrap gap-2">
                                                                {user.roles
                                                                    .length >
                                                                0 ? (
                                                                    user.roles
                                                                        .slice(
                                                                            0,
                                                                            2,
                                                                        )
                                                                        .map(
                                                                            (
                                                                                role,
                                                                            ) => (
                                                                                <Badge
                                                                                    key={
                                                                                        role
                                                                                    }
                                                                                    variant="secondary"
                                                                                >
                                                                                    {
                                                                                        role
                                                                                    }
                                                                                </Badge>
                                                                            ),
                                                                        )
                                                                ) : (
                                                                    <span className="text-muted-foreground">
                                                                        -
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                                            {formatDate(
                                                                user.created_at,
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No users have been created for this
                                        tenant yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="min-w-0 space-y-6">
                        <FacilityManagerSupportActions tenant={tenant} />

                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardContent className="space-y-4 p-6">
                                <div className="space-y-1">
                                    <h2 className="text-base font-semibold">
                                        Current Subscription
                                    </h2>
                                </div>
                                <Table>
                                    <TableBody>
                                        <TableRow>
                                            <TableHead className="w-40">
                                                Package
                                            </TableHead>
                                            <TableCell>
                                                {tenant.current_subscription
                                                    ?.package?.name ??
                                                    'No package'}
                                            </TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableHead>Status</TableHead>
                                            <TableCell>
                                                {tenant.current_subscription
                                                    ?.status_label ??
                                                    'No active subscription'}
                                            </TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableHead>Trial Ends</TableHead>
                                            <TableCell>
                                                {formatDate(
                                                    tenant.current_subscription
                                                        ?.trial_ends_at ?? null,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableHead>Period Ends</TableHead>
                                            <TableCell>
                                                {formatDate(
                                                    tenant.current_subscription
                                                        ?.current_period_ends_at ??
                                                        null,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableHead>Support Notes</TableHead>
                                            <TableCell>
                                                {usage.support_notes}
                                            </TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableHead>Last Note</TableHead>
                                            <TableCell>
                                                {formatDate(
                                                    usage.last_support_note_at,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardContent className="space-y-4 p-6">
                                <div className="space-y-1">
                                    <h2 className="text-base font-semibold">
                                        Recent Subscription History
                                    </h2>
                                </div>
                                {subscription_history.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Date</TableHead>
                                                    <TableHead>
                                                        Status
                                                    </TableHead>
                                                    <TableHead>
                                                        Package
                                                    </TableHead>
                                                    <TableHead>
                                                        Trial Ends
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {subscription_history.map(
                                                    (subscription) => (
                                                        <TableRow
                                                            key={
                                                                subscription.id
                                                            }
                                                        >
                                                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                                                {formatDate(
                                                                    subscription.created_at,
                                                                )}
                                                            </TableCell>
                                                            <TableCell>
                                                                <Badge variant="outline">
                                                                    {
                                                                        subscription.status_label
                                                                    }
                                                                </Badge>
                                                            </TableCell>
                                                            <TableCell>
                                                                {subscription
                                                                    .package
                                                                    ?.name ??
                                                                    'No package'}
                                                            </TableCell>
                                                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                                                {formatDate(
                                                                    subscription.trial_ends_at,
                                                                )}
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
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
