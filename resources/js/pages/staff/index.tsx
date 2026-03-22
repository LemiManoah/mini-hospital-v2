import StaffController from '@/actions/App/Http/Controllers/StaffController';
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
import { type Staff, type StaffIndexPageProps } from '@/types/staff';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Staff', href: StaffController.index.url() },
];

export default function StaffIndex({ staff, filters }: StaffIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: Staff[] = Array.isArray(staff) ? staff : (staff.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                StaffController.index.url(),
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['staff', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Staff" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Staff
                    </h2>
                    <Input
                        placeholder="Search staff..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('staff.create') ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link
                            href={StaffController.create.url()}
                            className="gap-2"
                        >
                            <span>+ Add Staff Member</span>
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Name
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Email
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Position
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Department
                            </TableHead>
                            <TableHead className="text-center text-xs font-semibold tracking-wider uppercase">
                                Status
                            </TableHead>
                            <TableHead className="text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((member) => (
                                <TableRow
                                    key={member.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="font-semibold text-zinc-900 dark:text-zinc-100">
                                        {formatIdentifierLabel(
                                            `${member.first_name} ${member.last_name}`,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-zinc-600 dark:text-zinc-300">
                                        {member.email}
                                    </TableCell>
                                    <TableCell className="text-zinc-600 dark:text-zinc-300">
                                        {member.position
                                            ? formatIdentifierLabel(
                                                  member.position.name,
                                              )
                                            : '-'}
                                    </TableCell>
                                    <TableCell className="text-zinc-600 dark:text-zinc-300">
                                        {(member.departments ?? []).length > 0
                                            ? formatIdentifierLabel(
                                                  member.departments
                                                      ?.map(
                                                          (department) =>
                                                              department.department_name,
                                                      )
                                                      .join(', ') ?? '',
                                              )
                                            : '-'}
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <span
                                            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase ${
                                                member.is_active
                                                    ? 'border-green-200 bg-green-100 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                    : 'border-zinc-200 bg-zinc-100 text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                                            }`}
                                        >
                                            {member.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission('staff.update') ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                    className="h-8 cursor-pointer border-zinc-200 px-3 text-xs shadow-sm hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                                                >
                                                    <Link
                                                        href={StaffController.edit.url(
                                                            {
                                                                staff: member.id,
                                                            },
                                                        )}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}

                                            {hasPermission('staff.delete') ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Staff Member"
                                                    description={`Are you sure you want to delete "${member.first_name} ${member.last_name}"? This action cannot be undone.`}
                                                    action={StaffController.destroy.form(
                                                        { staff: member.id },
                                                    )}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Staff member "${member.first_name} ${member.last_name}" deleted successfully.`,
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
                                    colSpan={6}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No staff members found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(staff) && staff.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={staff.prev_page_url ?? undefined}
                                    />
                                </PaginationItem>

                                {staff.links.map((link, idx) => {
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
                                        href={staff.next_page_url ?? undefined}
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
