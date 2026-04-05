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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    type LaboratoryDashboardMetric,
    type LaboratoryDashboardPageProps,
    type LaboratoryQueueRequest,
} from '@/types/laboratory';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    CheckCircle2,
    ClipboardList,
    FlaskConical,
    Microscope,
    ShieldCheck,
    TestTube,
} from 'lucide-react';
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
        ring: string;
        bg: string;
        text: string;
        subtext: string;
        icon: typeof Activity;
    }
> = {
    'Requests Today': {
        ring: 'ring-primary/20',
        bg: 'bg-primary/5',
        text: 'text-primary',
        subtext: 'text-primary/80',
        icon: ClipboardList,
    },
    'Urgent Open Requests': {
        ring: 'ring-destructive/20',
        bg: 'bg-destructive/5',
        text: 'text-destructive',
        subtext: 'text-destructive/80',
        icon: AlertTriangle,
    },
    'Pending Review': {
        ring: 'ring-amber-500/20',
        bg: 'bg-amber-500/5',
        text: 'text-amber-600',
        subtext: 'text-amber-600/80',
        icon: Microscope,
    },
    'Released Today': {
        ring: 'ring-emerald-500/20',
        bg: 'bg-emerald-500/5',
        text: 'text-emerald-600',
        subtext: 'text-emerald-600/80',
        icon: CheckCircle2,
    },
};

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
                            Laboratory Analytics
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Queue pressure, review load, and the latest patient
                            requests in the active branch.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Button asChild>
                            <Link href="/laboratory/incoming-investigations">
                                Open Incoming Queue
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/lab-test-catalogs">Test Catalog</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {metrics.map((metric) => (
                        <MetricCard key={metric.label} metric={metric} />
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card className="flex flex-col border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Request Status Mix</CardTitle>
                            <CardDescription>
                                High-level request volume by status
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
                                                <ChartTooltipContent hideLabel />
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
                            <CardTitle>Bench Workflow Pressure</CardTitle>
                            <CardDescription>
                                Item-level workload across the result lifecycle
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
                                        fill="var(--color-count)"
                                        radius={8}
                                    />
                                </BarChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="col-span-2 border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Recent Requests</CardTitle>
                                <CardDescription>
                                    The newest requests, with urgent work pushed
                                    to the top.
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href="/laboratory/incoming-investigations">
                                    View all
                                </Link>
                            </Button>
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

                    <div className="flex flex-col gap-6">
                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-lg">
                                    Quick Management
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3">
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href="/laboratory/incoming-investigations">
                                        <ClipboardList className="mr-2 h-4 w-4" />
                                        Incoming Queue
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href="/laboratory/enter-results">
                                        <TestTube className="mr-2 h-4 w-4" />
                                        Enter Results
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href="/laboratory/review-results">
                                        <Microscope className="mr-2 h-4 w-4" />
                                        Review Results
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href="/laboratory/view-results">
                                        <ShieldCheck className="mr-2 h-4 w-4" />
                                        Approved Results
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    className="justify-start"
                                    asChild
                                >
                                    <Link href="/lab-test-catalogs">
                                        <FlaskConical className="mr-2 h-4 w-4" />
                                        Test Catalog
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card className="border-none bg-primary/5 shadow-sm ring-1 ring-primary/20">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-lg text-primary">
                                    Turnaround Discipline
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    Keep urgent and critical requests flowing
                                    through review promptly. Results sitting in
                                    the bench queue delay clinical decisions.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function MetricCard({ metric }: { metric: LaboratoryDashboardMetric }) {
    const style = METRIC_STYLES[metric.label] ?? {
        ring: 'ring-border/50',
        bg: '',
        text: '',
        subtext: 'text-muted-foreground',
        icon: Activity,
    };
    const Icon = style.icon;

    return (
        <Card
            className={`overflow-hidden border-none shadow-sm ring-1 ${style.ring} ${style.bg}`}
        >
            <CardHeader className="space-y-0 pb-2">
                <CardDescription
                    className={`text-xs font-medium uppercase tracking-wider ${style.text}`}
                >
                    {metric.label}
                </CardDescription>
                <CardTitle className={`text-3xl font-bold ${style.text}`}>
                    {metric.value}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    className={`flex items-center gap-1 text-xs ${style.subtext}`}
                >
                    <Icon className="h-3.5 w-3.5" />
                    <span>{metric.hint}</span>
                </div>
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
                            <Link
                                href={
                                    item.workflow_stage === 'approved'
                                        ? '/laboratory/view-results'
                                        : item.workflow_stage ===
                                                'result_entered' ||
                                            item.workflow_stage === 'reviewed'
                                          ? '/laboratory/review-results'
                                          : item.workflow_stage ===
                                              'sample_collected'
                                            ? '/laboratory/enter-results'
                                            : '/laboratory/incoming-investigations'
                                }
                            >
                                Open Queue
                            </Link>
                        </Button>
                    </div>
                ))}
            </div>
        </div>
    );
}
