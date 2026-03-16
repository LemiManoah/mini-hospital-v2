import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
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
    type DoctorSchedule,
    type DoctorScheduleIndexPageProps,
} from '@/types/appointment';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointment-categories' },
    { title: 'Schedules', href: '/appointments/schedules' },
];

const labelize = (value: string): string =>
    value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function DoctorScheduleIndex({
    doctorSchedules,
    filters,
}: DoctorScheduleIndexPageProps) {
    const rows: DoctorSchedule[] = Array.isArray(doctorSchedules)
        ? doctorSchedules
        : (doctorSchedules.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) return;

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/appointments/schedules',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['doctorSchedules', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Doctor Schedules" />
            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Doctor Schedules
                    </h2>
                    <Input
                        placeholder="Search doctor or clinic..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                <Button
                    asChild
                    className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                >
                    <Link href="/appointments/schedules/create">
                        + Add Schedule
                    </Link>
                </Button>
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[1180px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Doctor</TableHead>
                            <TableHead>Clinic</TableHead>
                            <TableHead>Branch</TableHead>
                            <TableHead>Day</TableHead>
                            <TableHead>Time</TableHead>
                            <TableHead>Slots</TableHead>
                            <TableHead>Validity</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((schedule) => (
                                <TableRow key={schedule.id}>
                                    <TableCell className="font-semibold">
                                        {schedule.doctor?.name ||
                                            `${schedule.doctor?.first_name ?? ''} ${schedule.doctor?.last_name ?? ''}`.trim() ||
                                            'Unknown doctor'}
                                    </TableCell>
                                    <TableCell>
                                        {schedule.clinic?.name ||
                                            schedule.clinic?.clinic_name ||
                                            'Unknown clinic'}
                                    </TableCell>
                                    <TableCell>
                                        {schedule.branch?.name || (
                                            <span className="italic opacity-50">
                                                Active branch
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {labelize(schedule.day_of_week)}
                                    </TableCell>
                                    <TableCell>
                                        {schedule.start_time} - {schedule.end_time}
                                    </TableCell>
                                    <TableCell>
                                        {schedule.slot_duration_minutes} min /{' '}
                                        {schedule.max_patients} patients
                                    </TableCell>
                                    <TableCell>
                                        <div className="space-y-1">
                                            <p>{schedule.valid_from}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {schedule.valid_to ?? 'Open ended'}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {schedule.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/appointments/schedules/${schedule.id}/edit`}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <DeleteConfirmationModal
                                                title="Delete Doctor Schedule"
                                                description="Are you sure you want to delete this schedule? This action cannot be undone."
                                                action={{
                                                    method: 'delete',
                                                    action: `/appointments/schedules/${schedule.id}`,
                                                }}
                                                onSuccess={() =>
                                                    toast.success(
                                                        'Doctor schedule deleted successfully.',
                                                    )
                                                }
                                                trigger={
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                    >
                                                        Delete
                                                    </Button>
                                                }
                                            />
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={9}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No doctor schedules found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(doctorSchedules) &&
                doctorSchedules.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            doctorSchedules.prev_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>
                                {doctorSchedules.links.map((link, idx) => {
                                    const label = link.label
                                        .replace(/<[^>]*>/g, '')
                                        .trim();
                                    if (label === '...') {
                                        return (
                                            <PaginationItem
                                                key={`ellipsis-${idx}`}
                                            >
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
                                        href={
                                            doctorSchedules.next_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
