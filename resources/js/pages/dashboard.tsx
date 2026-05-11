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
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BadgeCheck,
    Banknote,
    CalendarClock,
    CalendarPlus,
    CheckCircle2,
    FlaskConical,
    Stethoscope,
    UserPlus,
    Users,
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
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface Metric {
    label: string;
    value: number;
    hint: string;
    icon: string;
    color: string;
}

interface VisitStatusCount {
    label: string;
    value: string;
    count: number;
}

interface AppointmentStatusCount {
    label: string;
    value: string;
    count: number;
}

interface Patient {
    id: string;
    patient_number: string;
    first_name: string;
    last_name: string;
}

interface Doctor {
    id: string;
    first_name: string;
    last_name: string;
}

interface Visit {
    id: string;
    visit_number: string;
    status: string;
    created_at: string;
    patient?: Patient | null;
    doctor?: Doctor | null;
}

interface Appointment {
    id: string;
    appointment_date: string;
    start_time: string;
    status: string;
    patient?: Patient | null;
    doctor?: Doctor | null;
    clinic?: { id: string; clinic_name: string } | null;
}

interface DashboardPageProps {
    metrics: Metric[];
    visit_status_counts: VisitStatusCount[];
    appointment_status_counts: AppointmentStatusCount[];
    recent_visits: Visit[];
    recent_appointments: Appointment[];
}

const METRIC_ICONS: Record<string, typeof Activity> = {
    users: Users,
    activity: Activity,
    calendar: CalendarClock,
    flask: FlaskConical,
    'user-check': UserPlus,
    'check-circle': CheckCircle2,
    banknote: Banknote,
    'badge-check': BadgeCheck,
};

const CHART_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

function VisitStatusBadge({ status }: { status: string }) {
    const variants: Record<
        string,
        'default' | 'secondary' | 'destructive' | 'outline'
    > = {
        registered: 'outline',
        in_progress: 'default',
        triage: 'secondary',
        awaiting_payment: 'secondary',
        completed: 'outline',
    };

    const labels: Record<string, string> = {
        registered: 'Registered',
        in_progress: 'In Progress',
        triage: 'Triaged',
        awaiting_payment: 'Awaiting Payment',
        completed: 'Completed',
    };

    return (
        <Badge variant={variants[status] || 'outline'}>
            {labels[status] || status}
        </Badge>
    );
}

function AppointmentStatusBadge({ status }: { status: string }) {
    const variants: Record<
        string,
        'default' | 'secondary' | 'destructive' | 'outline'
    > = {
        scheduled: 'outline',
        confirmed: 'default',
        checked_in: 'secondary',
        completed: 'outline',
        no_show: 'destructive',
        cancelled: 'destructive',
    };

    const labels: Record<string, string> = {
        scheduled: 'Scheduled',
        confirmed: 'Confirmed',
        checked_in: 'Checked In',
        completed: 'Completed',
        no_show: 'No Show',
        cancelled: 'Cancelled',
    };

    return (
        <Badge variant={variants[status] || 'outline'}>
            {labels[status] || status}
        </Badge>
    );
}

export default function Dashboard({
    metrics,
    visit_status_counts,
    appointment_status_counts,
    recent_visits,
    recent_appointments,
}: DashboardPageProps) {
    const { auth } = usePage<SharedData>().props;
    const firstName = auth.user?.name?.split(' ')[0] ?? 'there';

    const visitChartData = visit_status_counts.map((status) => ({
        status: status.label,
        count: status.count,
    }));

    const appointmentChartData = appointment_status_counts
        .filter((status) => status.count > 0)
        .map((status, index) => ({
            name: status.label,
            value: status.count,
            fill: CHART_COLORS[index % CHART_COLORS.length],
        }));

    const visitChartConfig = {
        count: {
            label: 'Visits',
            color: 'var(--chart-1)',
        },
    } satisfies ChartConfig;

    const appointmentChartConfig = {
        value: { label: 'Appointments' },
        ...Object.fromEntries(
            appointmentChartData.map((d, i) => [
                d.name,
                { label: d.name, color: CHART_COLORS[i % CHART_COLORS.length] },
            ]),
        ),
    } satisfies ChartConfig;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Welcome back, {firstName}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Here's the pulse of your facility today —{' '}
                            {new Date().toLocaleDateString(undefined, {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                            })}
                            .
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Button asChild>
                            <Link href="/patients">
                                <UserPlus className="mr-2 h-4 w-4" />
                                Register Patient
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/appointments/create">
                                <CalendarPlus className="mr-2 h-4 w-4" />
                                New Appointment
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {metrics.map((metric) => (
                        <MetricCard key={metric.label} metric={metric} />
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Visit Status Distribution</CardTitle>
                            <CardDescription>
                                Active visit workload across the patient
                                lifecycle
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer
                                config={visitChartConfig}
                                className="aspect-auto h-[300px] w-full"
                            >
                                <BarChart
                                    accessibilityLayer
                                    data={visitChartData}
                                    margin={{ top: 20 }}
                                >
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="status"
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

                    <Card className="flex flex-col border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle>Appointment Mix</CardTitle>
                            <CardDescription>
                                Appointment volume by status across all dates
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex-1 pb-0">
                            {appointmentChartData.length === 0 ? (
                                <div className="flex h-[300px] items-center justify-center text-sm text-muted-foreground">
                                    No appointments recorded yet.
                                </div>
                            ) : (
                                <ChartContainer
                                    config={appointmentChartConfig}
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
                                            data={appointmentChartData}
                                            dataKey="value"
                                            nameKey="name"
                                            innerRadius={60}
                                            strokeWidth={5}
                                        >
                                            {appointmentChartData.map(
                                                (entry, index) => (
                                                    <Cell
                                                        key={`cell-${index}`}
                                                        fill={entry.fill}
                                                    />
                                                ),
                                            )}
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
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-3">
                            <div>
                                <CardTitle>Recent Visits</CardTitle>
                                <CardDescription>
                                    Latest patient visits
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href="/visits">View all</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="p-0">
                            {recent_visits.length === 0 ? (
                                <div className="px-6 py-10 text-center text-sm text-muted-foreground">
                                    No visits yet.
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Patient</TableHead>
                                            <TableHead>Doctor</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recent_visits.map((visit) => (
                                            <TableRow
                                                key={visit.id}
                                                className="cursor-pointer"
                                            >
                                                <TableCell>
                                                    <Link
                                                        href={`/visits/${visit.id}`}
                                                        className="block font-medium hover:underline"
                                                    >
                                                        {visit.patient
                                                            ? `${visit.patient.first_name} ${visit.patient.last_name}`
                                                            : 'Unknown'}
                                                    </Link>
                                                    <span className="text-xs text-muted-foreground">
                                                        #{visit.visit_number}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {visit.doctor
                                                        ? `Dr. ${visit.doctor.last_name}`
                                                        : '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <VisitStatusBadge
                                                        status={visit.status}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader className="flex flex-row items-center justify-between pb-3">
                            <div>
                                <CardTitle>Upcoming Today</CardTitle>
                                <CardDescription>
                                    Appointments scheduled for today
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href="/appointments">View all</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="p-0">
                            {recent_appointments.length === 0 ? (
                                <div className="px-6 py-10 text-center text-sm text-muted-foreground">
                                    No appointments today.
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Patient</TableHead>
                                            <TableHead>Time</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recent_appointments.map(
                                            (appointment) => (
                                                <TableRow
                                                    key={appointment.id}
                                                    className="cursor-pointer"
                                                >
                                                    <TableCell>
                                                        <Link
                                                            href={`/appointments/${appointment.id}`}
                                                            className="block font-medium hover:underline"
                                                        >
                                                            {appointment.patient
                                                                ? `${appointment.patient.first_name} ${appointment.patient.last_name}`
                                                                : 'Unknown'}
                                                        </Link>
                                                        <span className="text-xs text-muted-foreground">
                                                            {appointment.clinic
                                                                ?.clinic_name ||
                                                                'No clinic'}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {appointment.start_time
                                                            ? new Date(
                                                                  `1970-01-01T${appointment.start_time}`,
                                                              ).toLocaleTimeString(
                                                                  [],
                                                                  {
                                                                      hour: '2-digit',
                                                                      minute: '2-digit',
                                                                  },
                                                              )
                                                            : '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <AppointmentStatusBadge
                                                            status={
                                                                appointment.status
                                                            }
                                                        />
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

function MetricCard({ metric }: { metric: Metric }) {
    const Icon = METRIC_ICONS[metric.icon] ?? Stethoscope;

    return (
        <Card className="border-none shadow-sm ring-1 ring-border/50">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardDescription className="text-xs font-medium tracking-wider uppercase">
                    {metric.label}
                </CardDescription>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <CardTitle className="text-3xl font-bold">
                    {metric.value}
                </CardTitle>
                <p className="mt-1 text-xs text-muted-foreground">
                    {metric.hint}
                </p>
            </CardContent>
        </Card>
    );
}
