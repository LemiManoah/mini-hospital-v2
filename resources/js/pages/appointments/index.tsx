import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    type Appointment,
    type AppointmentIndexPageProps,
} from '@/types/appointment';
import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, List } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
];

const weekDayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

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

function parseDate(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, (month || 1) - 1, day || 1);
}

function toDateInputValue(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function formatDate(value: string | null | undefined): string {
    if (!value) return 'N/A';

    return parseDate(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatTime(value: string | null | undefined): string {
    if (!value) return '';

    return value.slice(0, 5);
}

function matchesDate(appointment: Appointment, value: string): boolean {
    return appointment.appointment_date.slice(0, 10) === value;
}

function formatRangeLabel(fromDate: string, toDate: string): string {
    if (!fromDate && !toDate) {
        return 'Date range';
    }

    if (fromDate && toDate) {
        if (fromDate === toDate) {
            return formatDate(fromDate);
        }

        return `${formatDate(fromDate)} - ${formatDate(toDate)}`;
    }

    return formatDate(fromDate || toDate);
}

function enumerateDays(fromDate: string, toDate: string): string[] {
    if (!fromDate && !toDate) {
        return [];
    }

    const start = parseDate(fromDate || toDate);
    const end = parseDate(toDate || fromDate);
    const normalizedStart = start <= end ? start : end;
    const normalizedEnd = start <= end ? end : start;
    const days: string[] = [];
    const cursor = new Date(normalizedStart);

    while (cursor <= normalizedEnd) {
        days.push(toDateInputValue(cursor));
        cursor.setDate(cursor.getDate() + 1);
    }

    return days;
}

export default function AppointmentIndex({
    appointments,
    filters,
    statusOptions,
}: AppointmentIndexPageProps) {
    const rows: Appointment[] = Array.isArray(appointments)
        ? appointments
        : (appointments.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [fromDate, setFromDate] = useState(
        filters.from_date ?? toDateInputValue(new Date()),
    );
    const [toDate, setToDate] = useState(
        filters.to_date ?? toDateInputValue(new Date()),
    );
    const [view, setView] = useState(filters.view ?? 'list');

    useEffect(() => {
        setSearch(filters.search ?? '');
        setStatus(filters.status ?? 'all');
        setFromDate(filters.from_date ?? toDateInputValue(new Date()));
        setToDate(filters.to_date ?? toDateInputValue(new Date()));
        setView(filters.view ?? 'list');
    }, [filters.search, filters.status, filters.from_date, filters.to_date, filters.view]);

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            status === (filters.status ?? 'all') &&
            fromDate === (filters.from_date ?? toDateInputValue(new Date())) &&
            toDate === (filters.to_date ?? toDateInputValue(new Date())) &&
            view === (filters.view ?? 'list')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/appointments',
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                    from_date: fromDate || undefined,
                    to_date: toDate || undefined,
                    view,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['appointments', 'filters'],
                },
            );
        }, 250);

        return () => window.clearTimeout(timeoutId);
    }, [
        search,
        status,
        fromDate,
        toDate,
        view,
        filters.search,
        filters.status,
        filters.from_date,
        filters.to_date,
        filters.view,
    ]);

    const days = useMemo(
        () => enumerateDays(fromDate, toDate),
        [fromDate, toDate],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appointments" />
            <div className="m-4 space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold">Appointments</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage bookings, arrivals, and visit handoff.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/appointments/create">New Appointment</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4 lg:flex-1">
                            <Input
                                placeholder="Search patient, doctor, or clinic..."
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                            />
                            <Select value={status} onValueChange={setStatus}>
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
                        </div>

                        <ToggleGroup
                            type="single"
                            value={view}
                            onValueChange={(value) => {
                                if (value) {
                                    setView(value);
                                }
                            }}
                            variant="outline"
                        >
                            <ToggleGroupItem value="list" aria-label="List view">
                                <List className="mr-2 h-4 w-4" />
                                List
                            </ToggleGroupItem>
                            <ToggleGroupItem value="calendar" aria-label="Calendar view">
                                <CalendarDays className="mr-2 h-4 w-4" />
                                Calendar
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </div>
                </div>

                {view === 'list' ? (
                    <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <Table className="min-w-[1100px]">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Patient</TableHead>
                                    <TableHead>Doctor</TableHead>
                                    <TableHead>Clinic</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Time</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Visit</TableHead>
                                    <TableHead className="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.length > 0 ? (
                                    rows.map((appointment) => (
                                        <TableRow key={appointment.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">
                                                        {appointment.patient?.name || 'Unknown patient'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {appointment.patient?.patient_number}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {appointment.doctor?.name ||
                                                    `${appointment.doctor?.first_name ?? ''} ${appointment.doctor?.last_name ?? ''}`.trim() ||
                                                    'Unassigned'}
                                            </TableCell>
                                            <TableCell>
                                                {appointment.clinic?.name ||
                                                    appointment.clinic?.clinic_name ||
                                                    'Unassigned'}
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(appointment.appointment_date)}
                                            </TableCell>
                                            <TableCell>
                                                {formatTime(appointment.start_time)}
                                                {appointment.end_time
                                                    ? ` - ${formatTime(appointment.end_time)}`
                                                    : ''}
                                            </TableCell>
                                            <TableCell>
                                                <Badge className={badgeClass(appointment.status)}>
                                                    {appointment.status.replaceAll('_', ' ')}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {appointment.visit?.visit_number || 'Not checked in'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/appointments/${appointment.id}`}>
                                                        Open
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="py-12 text-center text-zinc-500 italic"
                                        >
                                            No appointments found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {!Array.isArray(appointments) && appointments.links?.length > 3 ? (
                            <div className="mt-4">
                                <Pagination>
                                    <PaginationContent>
                                        <PaginationItem>
                                            <PaginationPrevious
                                                href={appointments.prev_page_url ?? undefined}
                                            />
                                        </PaginationItem>
                                        {appointments.links.map((link, idx) => {
                                            const label = link.label
                                                .replace(/<[^>]*>/g, '')
                                                .trim();
                                            if (label === '...') {
                                                return (
                                                    <PaginationItem key={`ellipsis-${idx}`}>
                                                        <PaginationEllipsis />
                                                    </PaginationItem>
                                                );
                                            }

                                            if (/^\d+$/.test(label)) {
                                                return (
                                                    <PaginationItem key={label}>
                                                        <PaginationLink
                                                            href={link.url ?? undefined}
                                                            isActive={link.active}
                                                        >
                                                            {label}
                                                        </PaginationLink>
                                                    </PaginationItem>
                                                );
                                            }

                                            return null;
                                        })}
                                        <PaginationItem>
                                            <PaginationNext
                                                href={appointments.next_page_url ?? undefined}
                                            />
                                        </PaginationItem>
                                    </PaginationContent>
                                </Pagination>
                            </div>
                        ) : null}
                    </div>
                ) : (
                    <div className="space-y-4 rounded border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div>
                            <h2 className="text-lg font-semibold">
                                {formatRangeLabel(fromDate, toDate)}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Faster reading across the selected booking range.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            {days.map((day) => {
                                const dayAppointments = rows.filter((appointment) =>
                                    matchesDate(appointment, day),
                                );

                                return (
                                    <div
                                        key={day}
                                        className="min-h-72 rounded-xl border border-zinc-200 bg-zinc-50/60 p-3 dark:border-zinc-800 dark:bg-zinc-950/30"
                                    >
                                        <div className="mb-3 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                                            <p className="text-xs uppercase tracking-wide text-muted-foreground">
                                                {weekDayLabels[parseDate(day).getDay()]}
                                            </p>
                                            <p className="text-sm font-semibold">
                                                {formatDate(day)}
                                            </p>
                                        </div>

                                        <div className="space-y-3">
                                            {dayAppointments.length > 0 ? (
                                                dayAppointments.map((appointment) => (
                                                    <Link
                                                        key={appointment.id}
                                                        href={`/appointments/${appointment.id}`}
                                                        className="block rounded-lg border border-zinc-200 bg-white p-3 transition hover:border-zinc-400 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                                                    >
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p className="text-sm font-semibold">
                                                                    {formatTime(appointment.start_time)}
                                                                    {appointment.end_time
                                                                        ? ` - ${formatTime(appointment.end_time)}`
                                                                        : ''}
                                                                </p>
                                                                <p className="mt-1 text-sm">
                                                                    {appointment.patient?.name ||
                                                                        'Unknown patient'}
                                                                </p>
                                                            </div>
                                                            <Badge
                                                                className={badgeClass(
                                                                    appointment.status,
                                                                )}
                                                            >
                                                                {appointment.status.replaceAll('_', ' ')}
                                                            </Badge>
                                                        </div>
                                                        <div className="mt-3 space-y-1 text-xs text-muted-foreground">
                                                            <p>
                                                                Doctor:{' '}
                                                                {appointment.doctor?.name ||
                                                                    `${appointment.doctor?.first_name ?? ''} ${appointment.doctor?.last_name ?? ''}`.trim() ||
                                                                    'Unassigned'}
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
                                                    </Link>
                                                ))
                                            ) : (
                                                <div className="rounded-lg border border-dashed border-zinc-300 p-4 text-sm italic text-muted-foreground dark:border-zinc-700">
                                                    No bookings for this day.
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
