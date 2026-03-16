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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Appointment, type AppointmentIndexPageProps } from '@/types/appointment';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
];

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

function formatDate(value: string | null | undefined): string {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
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
    const [date, setDate] = useState(filters.date ?? '');

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            status === (filters.status ?? 'all') &&
            date === (filters.date ?? '')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/appointments',
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                    date: date || undefined,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['appointments', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, status, date, filters.search, filters.status, filters.date]);

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

                <div className="grid gap-3 md:grid-cols-3">
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
                        value={date}
                        onChange={(event) => setDate(event.target.value)}
                    />
                </div>

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
                                            {appointment.start_time.slice(0, 5)}
                                            {appointment.end_time
                                                ? ` - ${appointment.end_time.slice(0, 5)}`
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
                                        <PaginationPrevious href={appointments.prev_page_url ?? undefined} />
                                    </PaginationItem>
                                    {appointments.links.map((link, idx) => {
                                        const label = link.label.replace(/<[^>]*>/g, '').trim();
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
                                                    <PaginationLink href={link.url ?? undefined} isActive={link.active}>
                                                        {label}
                                                    </PaginationLink>
                                                </PaginationItem>
                                            );
                                        }

                                        return null;
                                    })}
                                    <PaginationItem>
                                        <PaginationNext href={appointments.next_page_url ?? undefined} />
                                    </PaginationItem>
                                </PaginationContent>
                            </Pagination>
                        </div>
                    ) : null}
                </div>
            </div>
        </AppLayout>
    );
}
