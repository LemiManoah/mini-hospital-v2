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
    type DoctorScheduleException,
    type DoctorScheduleExceptionIndexPageProps,
} from '@/types/appointment';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
    { title: 'Schedule Exceptions', href: '/appointments/exceptions' },
];

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return 'N/A';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatTimeRange(exception: DoctorScheduleException): string {
    if (exception.is_all_day) {
        return 'All day';
    }

    if (!exception.start_time || !exception.end_time) {
        return 'Blocked';
    }

    return `${exception.start_time.slice(0, 5)} - ${exception.end_time.slice(0, 5)}`;
}

const labelize = (value: string): string =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function DoctorScheduleExceptionIndex({
    exceptions,
    filters,
}: DoctorScheduleExceptionIndexPageProps) {
    const rows: DoctorScheduleException[] = Array.isArray(exceptions)
        ? exceptions
        : (exceptions.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/appointments/exceptions',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['exceptions', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Schedule Exceptions" />
            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Schedule Exceptions
                    </h2>
                    <Input
                        placeholder="Search doctor, clinic, type, or reason..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                <Button
                    asChild
                    className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                >
                    <Link href="/appointments/exceptions/create">
                        + Add Exception
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
                            <TableHead>Date</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Window</TableHead>
                            <TableHead>Reason</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((exception) => (
                                <TableRow key={exception.id}>
                                    <TableCell className="font-semibold">
                                        {exception.doctor?.name ||
                                            `${exception.doctor?.first_name ?? ''} ${exception.doctor?.last_name ?? ''}`.trim() ||
                                            'Unknown doctor'}
                                    </TableCell>
                                    <TableCell>
                                        {exception.clinic?.name ||
                                            exception.clinic?.clinic_name || (
                                                <span className="italic opacity-50">
                                                    All clinics
                                                </span>
                                            )}
                                    </TableCell>
                                    <TableCell>
                                        {exception.branch?.name || (
                                            <span className="italic opacity-50">
                                                Active branch
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {formatDate(exception.exception_date)}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {labelize(exception.type)}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {formatTimeRange(exception)}
                                    </TableCell>
                                    <TableCell className="max-w-sm">
                                        {exception.reason || (
                                            <span className="text-muted-foreground italic">
                                                No reason recorded
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/appointments/exceptions/${exception.id}/edit`}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <DeleteConfirmationModal
                                                title="Delete Schedule Exception"
                                                description="Are you sure you want to delete this schedule exception? This action cannot be undone."
                                                action={{
                                                    method: 'delete',
                                                    action: `/appointments/exceptions/${exception.id}`,
                                                }}
                                                onSuccess={() =>
                                                    toast.success(
                                                        'Schedule exception deleted successfully.',
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
                                    colSpan={8}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No schedule exceptions found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(exceptions) && exceptions.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            exceptions.prev_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>
                                {exceptions.links.map((link, idx) => {
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
                                            exceptions.next_page_url ??
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
