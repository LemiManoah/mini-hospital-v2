import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
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
import { FacilityManagerTenantHeader } from './components/facility-manager-tenant-header';
import {
    type FacilityManagerMetric,
    type FacilityManagerTenantSummary,
} from './types';

interface ActivityEvent {
    type: string;
    title: string;
    subject: string | null;
    timestamp: string | null;
}

interface FacilityManagerActivityProps {
    tenant: FacilityManagerTenantSummary;
    metrics: FacilityManagerMetric[];
    recent_activity: ActivityEvent[];
}

const formatDateTime = (value: string | null): string =>
    value
        ? new Date(value).toLocaleString('en-UG', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : 'Not set';

export default function FacilityManagerActivity({
    tenant,
    metrics,
    recent_activity,
}: FacilityManagerActivityProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        { title: 'Facilities', href: '/facility-manager/facilities' },
        { title: tenant.name, href: `/facility-manager/facilities/${tenant.id}` },
        {
            title: 'Activity',
            href: `/facility-manager/facilities/${tenant.id}/activity`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tenant.name} Activity`} />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link href={`/facility-manager/facilities/${tenant.id}`}>
                            <ArrowLeft className="h-4 w-4" />
                            Back to Overview
                        </Link>
                    </Button>
                </div>

                <FacilityManagerTenantHeader
                    tenant={tenant}
                    title="Activity"
                    description="Watch operational usage and the most recent actions recorded for this facility."
                />

                <FacilityManagerNav tenantId={tenant.id} current="activity" />

                <FacilityManagerMetrics metrics={metrics} />

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>
                            Latest operational events across visits, consultations, laboratory, pharmacy, and services.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Area</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>When</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recent_activity.length > 0 ? (
                                    recent_activity.map((event, index) => (
                                        <TableRow
                                            key={`${event.type}-${event.timestamp}-${index}`}
                                        >
                                            <TableCell>{event.type}</TableCell>
                                            <TableCell>{event.title}</TableCell>
                                            <TableCell>
                                                {event.subject ?? 'No subject'}
                                            </TableCell>
                                            <TableCell>
                                                {formatDateTime(event.timestamp)}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="py-12 text-center text-sm text-muted-foreground"
                                        >
                                            No recent activity has been recorded yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
