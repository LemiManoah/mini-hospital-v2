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
import {
    type Appointment,
    type AppointmentQueuePageProps,
} from '@/types/appointment';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
    { title: 'Queue', href: '/appointments/queue' },
];

const queueFilterDefaults = {
    doctor_id: 'all',
    clinic_id: 'all',
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

function formatDate(value: string | null | undefined): string {
    if (!value) return 'N/A';

    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function groupLabel(appointment: Appointment): string {
    const clinic =
        appointment.clinic?.name ||
        appointment.clinic?.clinic_name ||
        'No clinic';
    const doctor =
        appointment.doctor?.name ||
        `${appointment.doctor?.first_name ?? ''} ${appointment.doctor?.last_name ?? ''}`.trim() ||
        'Unassigned doctor';

    return `${clinic} / ${doctor}`;
}

export default function AppointmentQueue({
    appointments,
    filters,
    doctors,
    clinics,
}: AppointmentQueuePageProps) {
    const { fromDate, setFromDate, toDate, setToDate, values, setValue } =
        useDateRangeQueryFilters({
            route: '/appointments/queue',
            filters: {
                from_date: filters.from_date ?? '',
                to_date: filters.to_date ?? '',
                doctor_id: filters.doctor_id ?? 'all',
                clinic_id: filters.clinic_id ?? 'all',
            },
            defaults: queueFilterDefaults,
            only: ['appointments', 'filters'],
        });
    const { doctor_id: doctorId, clinic_id: clinicId } = values;

    const groups = appointments.reduce<Record<string, Appointment[]>>(
        (carry, appointment) => {
            const key = groupLabel(appointment);
            carry[key] ??= [];
            carry[key].push(appointment);

            return carry;
        },
        {},
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appointment Queue" />
            <div className="m-4 space-y-4">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold">Appointment Queue</h1>
                    <p className="text-sm text-muted-foreground">
                        Front-desk queue grouped by clinic and doctor across a
                        selected date range.
                    </p>
                </div>

                <div className="grid gap-3 rounded border bg-white p-4 shadow-sm md:grid-cols-4 dark:bg-zinc-900">
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
                        value={clinicId}
                        onValueChange={(value) => setValue('clinic_id', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="All clinics" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All clinics</SelectItem>
                            {clinics.map((clinic) => (
                                <SelectItem key={clinic.id} value={clinic.id}>
                                    {clinic.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={doctorId}
                        onValueChange={(value) => setValue('doctor_id', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="All doctors" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All doctors</SelectItem>
                            {doctors.map((doctor) => (
                                <SelectItem key={doctor.id} value={doctor.id}>
                                    {doctor.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="grid gap-4">
                    {Object.keys(groups).length > 0 ? (
                        Object.entries(groups).map(([label, items]) => (
                            <div
                                key={label}
                                className="rounded border bg-white p-4 shadow-sm dark:bg-zinc-900"
                            >
                                <h2 className="text-lg font-semibold">{label}</h2>
                                <div className="mt-4 space-y-3">
                                    {items.map((appointment) => (
                                        <div
                                            key={appointment.id}
                                            className="flex flex-col gap-3 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-3">
                                                    <p className="font-semibold">
                                                        {formatDate(appointment.appointment_date)}
                                                    </p>
                                                    <p className="font-semibold">
                                                        {formatTime(appointment.start_time)}
                                                    </p>
                                                    <Badge className={badgeClass(appointment.status)}>
                                                        {appointment.status.replaceAll('_', ' ')}
                                                    </Badge>
                                                </div>
                                                <p>{appointment.patient?.name ?? 'Unknown patient'}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {appointment.patient?.patient_number ?? 'N/A'} /{' '}
                                                    {appointment.visit?.visit_number ||
                                                        'Not checked in'}
                                                </p>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button variant="outline" asChild>
                                                    <Link href={`/appointments/${appointment.id}`}>
                                                        Open
                                                    </Link>
                                                </Button>
                                                {appointment.visit ? (
                                                    <Button asChild>
                                                        <Link href={`/visits/${appointment.visit.id}`}>
                                                            Visit
                                                        </Link>
                                                    </Button>
                                                ) : null}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="rounded border border-dashed bg-white p-8 text-center text-muted-foreground dark:bg-zinc-900">
                            No appointments found for the selected queue filters.
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
