import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDateRangeQueryFilters } from '@/hooks/use-date-range-query-filters';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type AppointmentMyPageProps } from '@/types/appointment';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
    { title: 'My Appointments', href: '/appointments/my' },
];

const myAppointmentFilterDefaults = {
    status: 'all',
};

const badgeClass = (status: string): string =>
    ({
        scheduled: 'bg-zinc-100 text-zinc-800',
        confirmed: 'bg-blue-100 text-blue-800',
        checked_in: 'bg-cyan-100 text-cyan-800',
        in_progress: 'bg-amber-100 text-amber-800',
        completed: 'bg-emerald-100 text-emerald-800',
        no_show: 'bg-orange-100 text-orange-800',
        cancelled: 'bg-red-100 text-red-800',
        rescheduled: 'bg-violet-100 text-violet-800',
    })[status] ?? 'bg-zinc-100 text-zinc-800';

function formatTime(value: string | null | undefined): string {
    if (!value) return '';

    return value.slice(0, 5);
}

export default function AppointmentMy({
    appointments,
    filters,
    statusOptions,
}: AppointmentMyPageProps) {
    const { fromDate, setFromDate, toDate, setToDate, values, setValue } =
        useDateRangeQueryFilters({
            route: '/appointments/my',
            filters: {
                from_date: filters.from_date ?? '',
                to_date: filters.to_date ?? '',
                status: filters.status ?? 'all',
            },
            defaults: myAppointmentFilterDefaults,
            only: ['appointments', 'filters'],
        });
    const { status } = values;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Appointments" />
            <div className="m-4 space-y-4">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold">My Appointments</h1>
                    <p className="text-sm text-muted-foreground">
                        Doctor-focused range view for booked and checked-in
                        patients.
                    </p>
                </div>

                <div className="grid gap-3 rounded border bg-white p-4 shadow-sm md:grid-cols-3 dark:bg-zinc-900">
                    <Input
                        type="date"
                        value={fromDate}
                        onChange={(event) => setFromDate(event.target.value)}
                    />
                    <Input
                        type="date"
                        value={toDate}
                        onChange={(event) => setToDate(event.target.value)}
                    />
                    <Select
                        value={status}
                        onValueChange={(value) => setValue('status', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {statusOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="grid gap-4">
                    {appointments.length > 0 ? (
                        appointments.map((appointment) => (
                            <div
                                key={appointment.id}
                                className="rounded border bg-white p-4 shadow-sm dark:bg-zinc-900"
                            >
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-3">
                                            <h2 className="text-lg font-semibold">
                                                {formatTime(appointment.start_time)}
                                                {appointment.end_time
                                                    ? ` - ${formatTime(appointment.end_time)}`
                                                    : ''}
                                            </h2>
                                            <Badge className={badgeClass(appointment.status)}>
                                                {appointment.status.replaceAll('_', ' ')}
                                            </Badge>
                                        </div>
                                        <p className="font-medium">
                                            {appointment.patient?.name ??
                                                'Unknown patient'}
                                        </p>
                                        <div className="space-y-1 text-sm text-muted-foreground">
                                            <p>
                                                MRN:{' '}
                                                {appointment.patient?.patient_number ??
                                                    'N/A'}
                                            </p>
                                            <p>
                                                Clinic:{' '}
                                                {appointment.clinic?.name ||
                                                    appointment.clinic?.clinic_name ||
                                                    'Unassigned'}
                                            </p>
                                            <p>
                                                Visit:{' '}
                                                {appointment.visit?.visit_number ||
                                                    'Not checked in'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button variant="outline" asChild>
                                            <Link href={`/appointments/${appointment.id}`}>
                                                Open Appointment
                                            </Link>
                                        </Button>
                                        {appointment.visit ? (
                                            <Button asChild>
                                                <Link href={`/visits/${appointment.visit.id}`}>
                                                    Open Visit
                                                </Link>
                                            </Button>
                                        ) : null}
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="rounded border border-dashed bg-white p-8 text-center text-muted-foreground dark:bg-zinc-900">
                            No appointments found for the selected date range.
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
