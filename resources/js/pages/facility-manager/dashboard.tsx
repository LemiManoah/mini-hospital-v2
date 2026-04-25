import { Badge } from '@/components/ui/badge';
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
import { Head, Link } from '@inertiajs/react';

interface Metric {
    label: string;
    value: number;
    hint: string;
}

interface FollowUpTenant {
    id: string;
    name: string;
    domain: string;
    onboarding_completed_at: string | null;
    current_subscription?: {
        status: string;
        status_label: string;
        package?: {
            name: string;
        } | null;
    } | null;
    counts: {
        branches: number;
        users: number;
        patients: number;
        visits: number;
    };
}

interface FacilityManagerDashboardProps {
    metrics: Metric[];
    follow_up_tenants: FollowUpTenant[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Facility Manager', href: '/facility-manager/dashboard' },
    { title: 'Dashboard', href: '/facility-manager/dashboard' },
];

const styleFor = (label: string): string => {
    if (label === 'Needs Follow-Up') return 'text-rose-600';
    if (label === 'Active Subscriptions') return 'text-emerald-600';
    if (label === 'Onboarded') return 'text-amber-600';

    return 'text-primary';
};

const onboardingLabel = (value: string | null): string =>
    value ? 'Onboarded' : 'Onboarding Open';

export default function FacilityManagerDashboard({
    metrics,
    follow_up_tenants,
}: FacilityManagerDashboardProps) {
    const { hasPermission } = usePermissions();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facility Manager" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Facility Manager
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Support-only control panel for onboarding,
                            subscriptions, tenant health, and workspace
                            intervention.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/facility-manager/facilities">
                                Open Facilities
                            </Link>
                        </Button>
                        {hasPermission('tenants.update') ? (
                            <Button asChild>
                                <Link href="/facility-manager/facilities/create">
                                    Create Facility
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {metrics.map((metric) => {
                        const color = styleFor(metric.label);

                        return (
                            <Card
                                key={metric.label}
                                className="border-none shadow-sm ring-1 ring-border/50"
                            >
                                <CardHeader className="space-y-0 pb-2">
                                    <CardDescription
                                        className={`text-xs font-medium tracking-wider uppercase ${color}`}
                                    >
                                        {metric.label}
                                    </CardDescription>
                                    <CardTitle
                                        className={`text-3xl font-bold ${color}`}
                                    >
                                        {metric.value}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-xs text-muted-foreground">
                                        {metric.hint}
                                    </p>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Facilities Needing Follow-Up</CardTitle>
                            <CardDescription>
                                Start with tenants that are missing onboarding,
                                lack a subscription, or are already past due.
                            </CardDescription>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/facility-manager/facilities">
                                View all facilities
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {follow_up_tenants.length === 0 ? (
                            <div className="rounded-lg border border-dashed px-4 py-12 text-center text-sm text-muted-foreground">
                                No facilities need immediate follow-up right
                                now.
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Facility</TableHead>
                                        <TableHead>Onboarding</TableHead>
                                        <TableHead>Subscription</TableHead>
                                        <TableHead>Users</TableHead>
                                        <TableHead>Patients</TableHead>
                                        <TableHead>Visits</TableHead>
                                        <TableHead className="text-right">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {follow_up_tenants.map((tenant) => (
                                        <TableRow key={tenant.id}>
                                            <TableCell>
                                                <div className="flex flex-col">
                                                    <span className="font-medium">
                                                        {tenant.name}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {tenant.domain}
                                                        .mini-hospital.com
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {onboardingLabel(
                                                        tenant.onboarding_completed_at,
                                                    )}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col gap-1">
                                                    <Badge variant="outline">
                                                        {tenant
                                                            .current_subscription
                                                            ?.status_label ??
                                                            'No subscription'}
                                                    </Badge>
                                                    <span className="text-xs text-muted-foreground">
                                                        {tenant
                                                            .current_subscription
                                                            ?.package?.name ??
                                                            'No package'}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {tenant.counts.users}
                                            </TableCell>
                                            <TableCell>
                                                {tenant.counts.patients}
                                            </TableCell>
                                            <TableCell>
                                                {tenant.counts.visits}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/facility-manager/facilities/${tenant.id}`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
