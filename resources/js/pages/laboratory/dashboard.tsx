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
import {
    type LaboratoryDashboardMetric,
    type LaboratoryDashboardPageProps,
    type LaboratoryQueueRequest,
} from '@/types/laboratory';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Laboratory', href: '/laboratory/dashboard' },
    { title: 'Dashboard', href: '/laboratory/dashboard' },
];

const labelize = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';

const requestVariant = (
    priority: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (priority === 'critical' || priority === 'stat') return 'destructive';
    if (priority === 'urgent') return 'secondary';

    return 'outline';
};

const workflowVariant = (
    value: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (value === 'approved') return 'default';
    if (value === 'reviewed' || value === 'result_entered') return 'secondary';
    if (value === 'cancelled' || value === 'rejected') return 'destructive';

    return 'outline';
};

export default function LaboratoryDashboard({
    metrics,
    request_status_counts,
    workflow_stage_counts,
    recent_requests,
}: LaboratoryDashboardPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laboratory Dashboard" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold">
                            Laboratory Dashboard
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Start here to see queue pressure, review load, and
                            the latest patient requests in the active branch.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/laboratory/worklist">
                                Open Worklist
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/lab-test-catalogs">Test Catalog</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric) => (
                        <MetricCard key={metric.label} metric={metric} />
                    ))}
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Request Status Mix</CardTitle>
                            <CardDescription>
                                High-level request volume by status.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2">
                            {request_status_counts.map((status) => (
                                <div
                                    key={status.value}
                                    className="rounded-lg border p-4"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="font-medium">
                                            {status.label}
                                        </p>
                                        <Badge variant="outline">
                                            {status.count}
                                        </Badge>
                                    </div>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        <Link
                                            href={`/laboratory/worklist?status=${status.value}`}
                                            className="underline underline-offset-4"
                                        >
                                            View matching requests
                                        </Link>
                                    </p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Bench Workflow Pressure</CardTitle>
                            <CardDescription>
                                Item-level workload across the result lifecycle.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {workflow_stage_counts.map((stage) => (
                                <div
                                    key={stage.value}
                                    className="flex items-center justify-between rounded-lg border p-4"
                                >
                                    <div className="flex items-center gap-3">
                                        <Badge
                                            variant={workflowVariant(
                                                stage.value,
                                            )}
                                        >
                                            {stage.label}
                                        </Badge>
                                        <p className="text-sm text-muted-foreground">
                                            Result workflow count
                                        </p>
                                    </div>
                                    <p className="text-2xl font-semibold">
                                        {stage.count}
                                    </p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                            <div className="flex flex-col gap-1">
                                <CardTitle>Recent Requests</CardTitle>
                                <CardDescription>
                                    The newest requests, with urgent work pushed
                                    to the top.
                                </CardDescription>
                            </div>
                            <Button variant="outline" asChild>
                                <Link href="/laboratory/worklist">
                                    See Full Queue
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        {recent_requests.length === 0 ? (
                            <div className="rounded-lg border border-dashed px-4 py-12 text-center text-sm text-muted-foreground">
                                No lab requests are available in the active
                                branch yet.
                            </div>
                        ) : (
                            recent_requests.map((request) => (
                                <RecentRequestCard
                                    key={request.id}
                                    request={request}
                                />
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function MetricCard({ metric }: { metric: LaboratoryDashboardMetric }) {
    return (
        <Card>
            <CardHeader>
                <CardDescription>{metric.label}</CardDescription>
                <CardTitle className="text-3xl">{metric.value}</CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-sm text-muted-foreground">{metric.hint}</p>
            </CardContent>
        </Card>
    );
}

function RecentRequestCard({ request }: { request: LaboratoryQueueRequest }) {
    const patient = request.visit?.patient ?? null;

    return (
        <div className="rounded-lg border p-4">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div className="flex flex-col gap-1">
                    <p className="font-medium">
                        {patient
                            ? `${patient.first_name} ${patient.last_name}`
                            : 'Unknown patient'}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Visit {request.visit?.visit_number ?? 'N/A'} | MRN{' '}
                        {patient?.patient_number ?? 'N/A'} | Requested{' '}
                        {new Date(request.request_date).toLocaleString()}
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant={requestVariant(request.priority)}>
                        {labelize(request.priority)}
                    </Badge>
                    <Badge variant="outline">{labelize(request.status)}</Badge>
                </div>
            </div>

            <div className="mt-4 flex flex-col gap-3">
                {request.items.map((item) => (
                    <div
                        key={item.id}
                        className="flex flex-col gap-3 rounded-lg border bg-muted/30 p-3 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div className="flex flex-col gap-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="font-medium">
                                    {item.test?.test_name ?? 'Lab test'}
                                </p>
                                <Badge
                                    variant={workflowVariant(
                                        item.workflow_stage,
                                    )}
                                >
                                    {labelize(item.workflow_stage)}
                                </Badge>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {item.test?.test_code ?? 'N/A'} |{' '}
                                {item.test?.category ?? 'Uncategorized'} |{' '}
                                {item.test?.specimen_type ?? 'Specimen not set'}
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href={`/laboratory/request-items/${item.id}`}>
                                {item.workflow_stage === 'pending'
                                    ? 'Receive and Open'
                                    : 'Open Workflow'}
                            </Link>
                        </Button>
                    </div>
                ))}
            </div>
        </div>
    );
}
