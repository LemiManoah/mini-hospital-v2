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
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

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

const iconPaths: Record<string, string> = {
    users: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    activity: 'M22 12h-4l-3 9L9 3l-3 9H2',
    calendar:
        'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z',
    flask: 'M10 2v7.31L6 15v4h12v-4l-4-5.69V2M10 2h4M10 2H8',
    'user-check':
        'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 3l-4 4',
    'check-circle': 'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3',
};

const colorClasses: Record<string, string> = {
    blue: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950',
    green: 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950',
    purple: 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950',
    orange: 'text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950',
    teal: 'text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950',
    emerald:
        'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950',
};

function MetricIcon({ icon, color }: { icon: string; color: string }) {
    const path = iconPaths[icon] || iconPaths.users;

    return (
        <div
            className={`flex h-12 w-12 items-center justify-center rounded-lg ${colorClasses[color] || colorClasses.blue}`}
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="h-6 w-6"
            >
                <path d={path} />
            </svg>
        </div>
    );
}

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
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold">Dashboard</h1>
                    <p className="text-sm text-muted-foreground">
                        Welcome back! Here's an overview of your facility.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {metrics.map((metric) => (
                        <Card key={metric.label}>
                            <CardContent className="flex flex-row items-center gap-4 p-6">
                                <MetricIcon
                                    icon={metric.icon}
                                    color={metric.color}
                                />
                                <div className="flex flex-col gap-1">
                                    <p className="text-sm text-muted-foreground">
                                        {metric.label}
                                    </p>
                                    <p className="text-3xl font-semibold">
                                        {metric.value}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {metric.hint}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-6 xl:grid-cols-[1fr_1fr]">
                    <Card>
                        <CardHeader>
                            <div className="flex flex-col gap-1">
                                <CardTitle>Visit Status</CardTitle>
                                <CardDescription>
                                    Current visit distribution across statuses.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {visit_status_counts.map((status) => (
                                <div
                                    key={status.value}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <div
                                            className={`h-2 w-2 rounded-full ${
                                                status.value === 'in_progress'
                                                    ? 'bg-blue-500'
                                                    : status.value ===
                                                        'awaiting_payment'
                                                      ? 'bg-orange-500'
                                                      : status.value ===
                                                          'completed'
                                                        ? 'bg-green-500'
                                                        : 'bg-gray-400'
                                            }`}
                                        />
                                        <p className="font-medium">
                                            {status.label}
                                        </p>
                                    </div>
                                    <p className="text-xl font-semibold">
                                        {status.count}
                                    </p>
                                </div>
                            ))}
                            <Button variant="outline" className="mt-2" asChild>
                                <Link href="/visits">View All Visits</Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex flex-col gap-1">
                                <CardTitle>Today's Appointments</CardTitle>
                                <CardDescription>
                                    Appointment distribution for today.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {appointment_status_counts.map((status) => (
                                <div
                                    key={status.value}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <div
                                            className={`h-2 w-2 rounded-full ${
                                                status.value === 'checked_in'
                                                    ? 'bg-green-500'
                                                    : status.value ===
                                                        'confirmed'
                                                      ? 'bg-blue-500'
                                                      : status.value ===
                                                          'no_show'
                                                        ? 'bg-red-500'
                                                        : 'bg-gray-400'
                                            }`}
                                        />
                                        <p className="font-medium">
                                            {status.label}
                                        </p>
                                    </div>
                                    <p className="text-xl font-semibold">
                                        {status.count}
                                    </p>
                                </div>
                            ))}
                            <Button variant="outline" className="mt-2" asChild>
                                <Link href="/appointments">
                                    View All Appointments
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <Card>
                        <CardHeader>
                            <div className="flex flex-col gap-1">
                                <CardTitle>Recent Visits</CardTitle>
                                <CardDescription>
                                    Latest patient visits in the active branch.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {recent_visits.length === 0 ? (
                                <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                    No visits yet in the active branch.
                                </div>
                            ) : (
                                recent_visits.map((visit) => (
                                    <Link
                                        key={visit.id}
                                        href={`/visits/${visit.id}`}
                                        className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                    >
                                        <div className="flex flex-col gap-1">
                                            <p className="font-medium">
                                                {visit.patient
                                                    ? `${visit.patient.first_name} ${visit.patient.last_name}`
                                                    : 'Unknown Patient'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Visit {visit.visit_number} •{' '}
                                                {visit.doctor
                                                    ? `Dr. ${visit.doctor.first_name} ${visit.doctor.last_name}`
                                                    : 'No doctor assigned'}
                                            </p>
                                        </div>
                                        <VisitStatusBadge
                                            status={visit.status}
                                        />
                                    </Link>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex flex-col gap-1">
                                <CardTitle>Upcoming Today</CardTitle>
                                <CardDescription>
                                    Appointments scheduled for today.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {recent_appointments.length === 0 ? (
                                <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                    No appointments scheduled for today.
                                </div>
                            ) : (
                                recent_appointments.map((appointment) => (
                                    <Link
                                        key={appointment.id}
                                        href={`/appointments/${appointment.id}`}
                                        className="flex flex-col gap-2 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                    >
                                        <div className="flex items-center justify-between">
                                            <p className="font-medium">
                                                {appointment.patient
                                                    ? `${appointment.patient.first_name} ${appointment.patient.last_name}`
                                                    : 'Unknown Patient'}
                                            </p>
                                            <AppointmentStatusBadge
                                                status={appointment.status}
                                            />
                                        </div>
                                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                            <span>
                                                {appointment.start_time
                                                    ? new Date(
                                                          `1970-01-01T${appointment.start_time}`,
                                                      ).toLocaleTimeString([], {
                                                          hour: '2-digit',
                                                          minute: '2-digit',
                                                      })
                                                    : 'No time'}
                                            </span>
                                            <span>•</span>
                                            <span>
                                                {appointment.clinic
                                                    ?.clinic_name ||
                                                    'No clinic'}
                                            </span>
                                        </div>
                                    </Link>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Button
                        variant="outline"
                        className="h-20 flex-col gap-2"
                        asChild
                    >
                        <Link href="/patients">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                />
                            </svg>
                            <span>Register Patient</span>
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        className="h-20 flex-col gap-2"
                        asChild
                    >
                        <Link href="/appointments/create">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            <span>New Appointment</span>
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        className="h-20 flex-col gap-2"
                        asChild
                    >
                        <Link href="/laboratory/dashboard">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"
                                />
                            </svg>
                            <span>Lab Dashboard</span>
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        className="h-20 flex-col gap-2"
                        asChild
                    >
                        <Link href="/visits">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                            <span>All Visits</span>
                        </Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
