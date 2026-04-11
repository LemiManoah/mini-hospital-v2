import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, ShieldCheck } from 'lucide-react';

import { FacilityManagerMetrics } from './components/facility-manager-metrics';
import { FacilityManagerNav } from './components/facility-manager-nav';
import { FacilityManagerPagination } from './components/facility-manager-pagination';
import { FacilityManagerTenantHeader } from './components/facility-manager-tenant-header';
import {
    type FacilityManagerMetric,
    type FacilityManagerTenantSummary,
    type PaginatedFacilityManagerList,
} from './types';

interface SubscriptionRow {
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

interface FacilityManagerSubscriptionsProps {
    tenant: FacilityManagerTenantSummary;
    metrics: FacilityManagerMetric[];
    current_subscription: SubscriptionRow | null;
    subscription_history: PaginatedFacilityManagerList<SubscriptionRow>;
}

const formatDate = (value: string | null): string =>
    value
        ? new Date(value).toLocaleDateString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
          })
        : 'Not set';

export default function FacilityManagerSubscriptions({
    tenant,
    metrics,
    current_subscription,
    subscription_history,
}: FacilityManagerSubscriptionsProps) {
    const { hasPermission } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        { title: 'Facilities', href: '/facility-manager/facilities' },
        {
            title: tenant.name,
            href: `/facility-manager/facilities/${tenant.id}`,
        },
        {
            title: 'Subscriptions',
            href: `/facility-manager/facilities/${tenant.id}/subscriptions`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tenant.name} Subscriptions`} />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link
                            href={`/facility-manager/facilities/${tenant.id}`}
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Overview
                        </Link>
                    </Button>
                </div>

                <FacilityManagerTenantHeader
                    tenant={tenant}
                    title="Subscriptions"
                    description="Handle package status, billing follow-up, and onboarding lifecycle actions from one place."
                />

                <FacilityManagerNav
                    tenantId={tenant.id}
                    current="subscriptions"
                />

                <FacilityManagerMetrics metrics={metrics} />

                <div className="grid gap-6 xl:grid-cols-[1fr_0.8fr]">
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Current Subscription</CardTitle>
                            <CardDescription>
                                The latest subscription state for this facility.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <StatBox
                                title="Package"
                                value={
                                    current_subscription?.package?.name ??
                                    'Not set'
                                }
                            />
                            <StatBox
                                title="Status"
                                value={
                                    current_subscription?.status_label ??
                                    'No subscription'
                                }
                            />
                            <StatBox
                                title="Trial Ends"
                                value={formatDate(
                                    current_subscription?.trial_ends_at ?? null,
                                )}
                            />
                            <StatBox
                                title="Current Period Ends"
                                value={formatDate(
                                    current_subscription?.current_period_ends_at ??
                                        null,
                                )}
                            />
                        </CardContent>
                    </Card>

                    {hasPermission('tenants.update') ? (
                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>Support Actions</CardTitle>
                                <CardDescription>
                                    Tenant switch, billing intervention, and
                                    onboarding controls.
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

                                {tenant.onboarding_completed_at ? (
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

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader>
                        <CardTitle>Subscription History</CardTitle>
                        <CardDescription>
                            Recorded subscription changes for this facility.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Package</TableHead>
                                    <TableHead>Trial Ends</TableHead>
                                    <TableHead>Period Ends</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {subscription_history.data.length > 0 ? (
                                    subscription_history.data.map(
                                        (subscription) => (
                                            <TableRow key={subscription.id}>
                                                <TableCell>
                                                    {formatDate(
                                                        subscription.created_at,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {subscription.status_label}
                                                </TableCell>
                                                <TableCell>
                                                    {subscription.package
                                                        ?.name ?? 'No package'}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDate(
                                                        subscription.trial_ends_at,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDate(
                                                        subscription.current_period_ends_at,
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ),
                                    )
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-12 text-center text-sm text-muted-foreground"
                                        >
                                            No subscription history is available
                                            yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <FacilityManagerPagination
                    links={subscription_history.links}
                    prevPageUrl={subscription_history.prev_page_url}
                    nextPageUrl={subscription_history.next_page_url}
                />
            </div>
        </AppLayout>
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
