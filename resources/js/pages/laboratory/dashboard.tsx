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
    ChartConfig,
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
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
import {
    type LaboratoryDashboardMetric,
    type LaboratoryDashboardPageProps,
    type LaboratoryQueueRequest,
} from '@/types/laboratory';
import { Head, Link } from '@inertiajs/react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    XAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Laboratory', href: '/laboratory/dashboard' },
    { title: 'Dashboard', href: '/laboratory/dashboard' },
];

const COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

const METRIC_STYLES: Record<
    string,
    {
        label: string;
        number: string;
    }
> = {
    'Requests Today': {
        label: 'text-primary',
        number: 'text-primary',
    },
    'Urgent Open Requests': {
        label: 'text-rose-600',
        number: 'text-rose-600',
    },
    'Pending Review': {
        label: 'text-amber-600',
        number: 'text-amber-600',
    },
    'Released Today': {
        label: 'text-emerald-600',
        number: 'text-emerald-600',
    },
    'Out of Stock': {
        label: 'text-rose-600',
        number: 'text-rose-600',
    },
    'Low Stock': {
        label: 'text-amber-600',
        number: 'text-amber-600',
    },
    'Expiring Soon': {
        label: 'text-amber-600',
        number: 'text-amber-600',
    },
    'Expired Stock': {
        label: 'text-slate-700',
        number: 'text-slate-700',
    },
};

const labelize = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';

const badgeVariant = (
    value: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (value === 'critical' || value === 'stat') return 'destructive';
    if (
        value === 'urgent' ||
        value === 'result_entered' ||
        value === 'reviewed'
    ) {
        return 'secondary';
    }
    if (value === 'approved') return 'default';

    return 'outline';
};

const queueMetaForRequest = (
    request: LaboratoryQueueRequest,
): { label: string; href: string } => {
    if (request.items.some((item) => item.workflow_stage === 'approved')) {
        return {
            label: 'View Results',
            href: '/laboratory/view-results',
        };
    }

    if (
        request.items.some(
            (item) =>
                item.workflow_stage === 'reviewed' ||
                item.workflow_stage === 'result_entered',
        )
    ) {
        return {
            label: 'Review Results',
            href: '/laboratory/review-results',
        };
    }

    if (
        request.items.some((item) => item.workflow_stage === 'sample_collected')
    ) {
        return {
            label: 'Enter Results',
            href: '/laboratory/enter-results',
        };
    }

    return {
        label: 'Incoming Queue',
        href: '/laboratory/incoming-investigations',
    };
};

export default function LaboratoryDashboard({
    metrics,
    stock_metrics,
    request_status_counts,
    workflow_stage_counts,
    recent_requests,
}: LaboratoryDashboardPageProps) {
    const statusData = request_status_counts
        .filter((status) => status.count > 0)
        .map((status, index) => ({
            name: status.label,
            value: status.count,
            fill: COLORS[index % COLORS.length],
        }));

    const workflowData = workflow_stage_counts.map((stage) => ({
        stage: stage.label,
        count: stage.count,
    }));

    const statusConfig = {
        value: {
            label: 'Requests',
        },
        ...Object.fromEntries(
            statusData.map((d, i) => [
                d.name,
                { label: d.name, color: COLORS[i % COLORS.length] },
            ]),
        ),
    } satisfies ChartConfig;

    const workflowConfig = {
        count: {
            label: 'Items',
            color: 'var(--chart-2)',
        },
    } satisfies ChartConfig;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laboratory Dashboard" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Laboratory Dashboard
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Queue load, released results, and lab stock for the
                            active branch.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Button asChild>
                            <Link href="/laboratory/incoming-investigations">
                                Open Incoming Queue
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/laboratory/stock">Lab Stock</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {metrics.map((metric) => (
                        <MetricCard key={metric.label} metric={metric} />
                    ))}
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {stock_metrics.map((metric) => (
                        <MetricCard key={metric.label} metric={metric} />
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Request Status Mix</CardTitle>
                            <CardDescription>
                                High-level request volume by status.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex-1 pb-0">
                            {statusData.length === 0 ? (
                                <div className="flex h-[300px] items-center justify-center text-sm text-muted-foreground">
                                    No request data to display yet.
                                </div>
                            ) : (
                                <ChartContainer
                                    config={statusConfig}
                                    className="mx-auto aspect-square max-h-[300px]"
                                >
                                    <PieChart>
                                        <ChartTooltip
                                            cursor={false}
                                            content={
                                                <ChartTooltipContent
                                                    hideLabel
                                                />
                                            }
                                        />
                                        <Pie
                                            data={statusData}
                                            dataKey="value"
                                            nameKey="name"
                                            innerRadius={60}
                                            strokeWidth={5}
                                        >
                                            {statusData.map((entry, index) => (
                                                <Cell
                                                    key={`cell-${index}`}
                                                    fill={entry.fill}
                                                />
                                            ))}
                                        </Pie>
                                        <ChartLegend
                                            content={
                                                <ChartLegendContent nameKey="name" />
                                            }
                                            className="-translate-y-2 flex-wrap gap-2 [&>*]:basis-1/4 [&>*]:justify-center"
                                        />
                                    </PieChart>
                                </ChartContainer>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Lab Workflow Stages</CardTitle>
                            <CardDescription>
                                Item-level workload across the laboratory result
                                process.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer
                                config={workflowConfig}
                                className="aspect-auto h-[300px] w-full"
                            >
                                <BarChart
                                    accessibilityLayer
                                    data={workflowData}
                                    margin={{ top: 20 }}
                                >
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="stage"
                                        tickLine={false}
                                        tickMargin={10}
                                        axisLine={false}
                                    />
                                    <ChartTooltip
                                        cursor={false}
                                        content={
                                            <ChartTooltipContent hideLabel />
                                        }
                                    />
                                    <Bar
                                        dataKey="count"
                                        fill="var(--chart-2)"
                                        radius={8}
                                    />
                                </BarChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>
                </div>

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Recent Requests</CardTitle>
                            <CardDescription>
                                Latest patients and the tests they were sent
                                for.
                            </CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/laboratory/incoming-investigations">
                                View all
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recent_requests.length === 0 ? (
                            <div className="rounded-lg border border-dashed px-4 py-12 text-center text-sm text-muted-foreground">
                                No lab requests are available in the active
                                branch yet.
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Patient</TableHead>
                                        <TableHead>Tests</TableHead>
                                        <TableHead>Requested</TableHead>
                                        <TableHead>Priority</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Queue</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recent_requests.map((request) => (
                                        <RecentRequestRow
                                            key={request.id}
                                            request={request}
                                        />
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

function MetricCard({ metric }: { metric: LaboratoryDashboardMetric }) {
    const style = METRIC_STYLES[metric.label] ?? {
        label: 'text-foreground',
        number: 'text-foreground',
    };

    return (
        <Card className="border-none shadow-sm ring-1 ring-border/50">
            <CardHeader className="space-y-0 pb-2">
                <CardDescription
                    className={`text-xs font-medium tracking-wider uppercase ${style.label}`}
                >
                    {metric.label}
                </CardDescription>
                <CardTitle className={`text-3xl font-bold ${style.number}`}>
                    {metric.value}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-xs text-muted-foreground">{metric.hint}</p>
            </CardContent>
        </Card>
    );
}

function RecentRequestRow({ request }: { request: LaboratoryQueueRequest }) {
    const patient = request.visit?.patient ?? null;
    const queue = queueMetaForRequest(request);

    return (
        <TableRow>
            <TableCell className="align-top">
                <div className="flex flex-col">
                    <span className="font-medium">
                        {patient
                            ? `${patient.first_name} ${patient.last_name}`
                            : 'Unknown patient'}
                    </span>
                </div>
            </TableCell>
            <TableCell className="max-w-[28rem] align-top">
                <div className="flex flex-col gap-1 whitespace-normal">
                    {request.items.map((item) => (
                        <span key={item.id} className="text-sm">
                            {item.test?.test_name ?? 'Lab test'}
                        </span>
                    ))}
                </div>
            </TableCell>
            <TableCell className="align-top text-sm text-muted-foreground">
                {new Date(request.request_date).toLocaleString()}
            </TableCell>
            <TableCell className="align-top">
                <Badge variant={badgeVariant(request.priority)}>
                    {labelize(request.priority)}
                </Badge>
            </TableCell>
            <TableCell className="align-top">
                <Badge variant={badgeVariant(request.status)}>
                    {labelize(request.status)}
                </Badge>
            </TableCell>
            <TableCell className="align-top">
                <Button variant="outline" size="sm" asChild>
                    <Link href={queue.href}>{queue.label}</Link>
                </Button>
            </TableCell>
        </TableRow>
    );
}
