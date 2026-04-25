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

import { FacilityManagerNav } from './components/facility-manager-nav';
import { FacilityManagerTenantHeader } from './components/facility-manager-tenant-header';
import {
    type FacilityHealthSummary,
    type FacilityManagerTenantSummary,
} from './types';

interface FacilityManagerAuditProps {
    tenant: FacilityManagerTenantSummary;
    health: FacilityHealthSummary;
}

const badgeClassFor = (status: string): string => {
    switch (status) {
        case 'critical':
            return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300';
        case 'warning':
            return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300';
        default:
            return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300';
    }
};

export default function FacilityManagerAudit({
    tenant,
    health,
}: FacilityManagerAuditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Manager', href: '/facility-manager/dashboard' },
        { title: 'Facilities', href: '/facility-manager/facilities' },
        {
            title: tenant.name,
            href: `/facility-manager/facilities/${tenant.id}`,
        },
        {
            title: 'Audit',
            href: `/facility-manager/facilities/${tenant.id}/audit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${tenant.name} Audit`} />

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
                    title="Configuration and readiness audit"
                    description="Review the checks below to see what is healthy, what needs follow-up, and what blocks operational readiness."
                    actions={
                        <Badge
                            variant="outline"
                            className={badgeClassFor(health.status)}
                        >
                            {health.status_label}
                        </Badge>
                    }
                />

                <FacilityManagerNav tenantId={tenant.id} current="audit" />

                <div className="grid gap-4 md:grid-cols-4">
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider uppercase">
                                Total Checks
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold">
                                {health.summary.total_checks}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider text-emerald-700 uppercase dark:text-emerald-300">
                                Passed
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold text-emerald-700 dark:text-emerald-300">
                                {health.summary.passed}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider text-amber-700 uppercase dark:text-amber-300">
                                Warnings
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold text-amber-700 dark:text-amber-300">
                                {health.summary.warnings}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="space-y-0 pb-2">
                            <CardDescription className="text-xs font-medium tracking-wider text-rose-700 uppercase dark:text-rose-300">
                                Critical
                            </CardDescription>
                            <CardTitle className="text-3xl font-bold text-rose-700 dark:text-rose-300">
                                {health.summary.critical}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Audit Checks</CardTitle>
                            <CardDescription>
                                Each check shows the current state and the next
                                recommended action for support.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {health.checks.map((check) => (
                                <div
                                    key={check.key}
                                    className="rounded-2xl border p-4"
                                >
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="space-y-1">
                                            <p className="font-medium">
                                                {check.label}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {check.detail}
                                            </p>
                                        </div>
                                        <Badge
                                            variant="outline"
                                            className={badgeClassFor(
                                                check.status,
                                            )}
                                        >
                                            {check.status_label}
                                        </Badge>
                                    </div>
                                    <div className="mt-3 rounded-xl bg-muted/35 px-3 py-2 text-sm text-muted-foreground">
                                        {check.recommendation}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Recommended Workflow</CardTitle>
                            <CardDescription>
                                Use these next steps to unblock the facility in
                                order of urgency.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {health.recommendations.length > 0 ? (
                                health.recommendations.map((recommendation) => (
                                    <div
                                        key={recommendation}
                                        className="rounded-xl bg-muted/35 px-3 py-3 text-sm text-muted-foreground"
                                    >
                                        {recommendation}
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    This facility currently passes all audit
                                    checks.
                                </p>
                            )}

                            <div className="flex flex-col gap-2 pt-2">
                                <Button asChild>
                                    <Link
                                        href={`/facility-manager/impersonation?facility_id=${tenant.id}`}
                                    >
                                        Impersonate Facility User
                                    </Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/facility-manager/facilities/${tenant.id}/support-notes`}
                                    >
                                        Open Support Notes
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
