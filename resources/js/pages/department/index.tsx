import DepartmentController from '@/actions/App/Http/Controllers/DepartmentController';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
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
import { formatIdentifierLabel } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import {
    type Department,
    type DepartmentIndexPageProps,
} from '@/types/department';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Departments', href: DepartmentController.index.url() },
];

export default function DepartmentIndex({
    departments,
    filters,
}: DepartmentIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: Department[] = Array.isArray(departments)
        ? departments
        : (departments.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                DepartmentController.index.url(),
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['departments', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Departments" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Departments
                    </h2>
                    <Input
                        placeholder="Search departments..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('departments.create') ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link
                            href={DepartmentController.create.url()}
                            className="gap-2"
                        >
                            <span>+ Add Department</span>
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[120px] text-xs font-semibold tracking-wider uppercase">
                                Code
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Department Name
                            </TableHead>
                            <TableHead className="w-[150px] text-xs font-semibold tracking-wider uppercase">
                                Type
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Location
                            </TableHead>
                            <TableHead className="w-[100px] text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((dept) => (
                                <TableRow
                                    key={dept.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                        {dept.department_code}
                                    </TableCell>
                                    <TableCell className="font-medium text-zinc-900 dark:text-zinc-100">
                                        {formatIdentifierLabel(
                                            dept.department_name,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-col gap-1">
                                            <span
                                                className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase ${
                                                    dept.is_clinical
                                                        ? 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                                        : 'border-zinc-200 bg-zinc-100 text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                                                }`}
                                            >
                                                {dept.is_clinical
                                                    ? 'Clinical'
                                                    : 'Administrative'}
                                            </span>
                                            {!dept.is_active && (
                                                <span className="inline-flex w-fit items-center rounded-full border border-red-200 bg-red-100 px-2 py-0.5 text-[10px] font-bold tracking-tight text-red-800 uppercase dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                    Inactive
                                                </span>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {dept.location || (
                                            <span className="italic opacity-50">
                                                No location info
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission(
                                                'departments.update',
                                            ) ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                    className="h-8 cursor-pointer border-zinc-200 px-3 text-xs shadow-sm hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                                                >
                                                    <Link
                                                        href={DepartmentController.edit.url(
                                                            {
                                                                department:
                                                                    dept.id,
                                                            },
                                                        )}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}

                                            {hasPermission(
                                                'departments.delete',
                                            ) ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Department"
                                                    description={`Are you sure you want to delete the department "${formatIdentifierLabel(dept.department_name)}"? This action cannot be undone.`}
                                                    action={DepartmentController.destroy.form(
                                                        {
                                                            department: dept.id,
                                                        },
                                                    )}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Department "${formatIdentifierLabel(dept.department_name)}" deleted successfully.`,
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            className="h-8 cursor-pointer px-3 text-xs shadow-sm"
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
                                    No departments found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(departments) &&
                departments.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            departments.prev_page_url ??
                                            undefined
                                        }
                                    />
                                </PaginationItem>

                                {departments.links.map((link, idx) => {
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
                                            departments.next_page_url ??
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
