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
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import {
    type AppointmentCategory,
    type AppointmentCategoryIndexPageProps,
} from '@/types/appointment';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointment Categories', href: '/appointment-categories' },
];

export default function AppointmentCategoryIndex({
    appointmentCategories,
    filters,
}: AppointmentCategoryIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: AppointmentCategory[] = Array.isArray(appointmentCategories)
        ? appointmentCategories
        : (appointmentCategories.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) return;

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/appointment-categories',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['appointmentCategories', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appointment Categories" />
            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Appointment Categories
                    </h2>
                    <Input
                        placeholder="Search categories..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('appointment_categories.create') ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link href="/appointment-categories/create">
                            + Add Category
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[980px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Clinic</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((category) => (
                                <TableRow key={category.id}>
                                    <TableCell className="font-semibold">
                                        {category.name}
                                    </TableCell>
                                    <TableCell>
                                        {category.clinic?.name ||
                                            category.clinic?.clinic_name || (
                                                <span className="italic opacity-50">
                                                    All clinics
                                                </span>
                                            )}
                                    </TableCell>
                                    <TableCell className="max-w-[360px] whitespace-normal">
                                        {category.description || (
                                            <span className="italic opacity-50">
                                                No description provided
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {category.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission(
                                                'appointment_categories.update',
                                            ) ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/appointment-categories/${category.id}/edit`}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}
                                            {hasPermission(
                                                'appointment_categories.delete',
                                            ) ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Appointment Category"
                                                    description={`Are you sure you want to delete "${category.name}"? This action cannot be undone.`}
                                                    action={{
                                                        method: 'delete',
                                                        action: `/appointment-categories/${category.id}`,
                                                    }}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Appointment category "${category.name}" deleted successfully.`,
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
                                            ) : null}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No appointment categories found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(appointmentCategories) &&
                appointmentCategories.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            appointmentCategories.prev_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>
                                {appointmentCategories.links.map(
                                    (link, idx) => {
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
                                                        href={
                                                            link.url ??
                                                            undefined
                                                        }
                                                        isActive={link.active}
                                                    >
                                                        {label}
                                                    </PaginationLink>
                                                </PaginationItem>
                                            );
                                        }

                                        return null;
                                    },
                                )}
                                <PaginationItem>
                                    <PaginationNext
                                        href={
                                            appointmentCategories.next_page_url ??
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
