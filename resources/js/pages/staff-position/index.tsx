import StaffPositionController from '@/actions/App/Http/Controllers/StaffPositionController';
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
    type StaffPosition,
    type StaffPositionIndexPageProps,
} from '@/types/staff-position';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Staff Positions', href: StaffPositionController.index.url() },
];

export default function StaffPositionIndex({
    positions,
    filters,
}: StaffPositionIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: StaffPosition[] = Array.isArray(positions)
        ? positions
        : (positions.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                StaffPositionController.index.url(),
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['positions', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Staff Positions" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Staff Positions
                    </h2>
                    <Input
                        placeholder="Search positions..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('staff_positions.create') ? (
                    <Button
                        asChild
                        className="shrink-0 border border-zinc-200 shadow-sm dark:border-zinc-800"
                    >
                        <Link
                            href={StaffPositionController.create.url()}
                            className="gap-2"
                        >
                            <span>+ Add Position</span>
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[300px] text-xs font-semibold tracking-wider uppercase">
                                Name
                            </TableHead>
                            <TableHead className="w-[150px] text-xs font-semibold tracking-wider uppercase">
                                Status
                            </TableHead>
                            <TableHead className="text-xs font-semibold tracking-wider uppercase">
                                Description
                            </TableHead>
                            <TableHead className="w-[150px] text-right text-xs font-semibold tracking-wider uppercase">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((position) => (
                                <TableRow
                                    key={position.id}
                                    className="group transition-colors"
                                >
                                    <TableCell className="font-semibold text-zinc-900 dark:text-zinc-100">
                                        {formatIdentifierLabel(position.name)}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold tracking-tight uppercase ${
                                                position.is_active
                                                    ? 'border-green-200 bg-green-100 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                    : 'border-zinc-200 bg-zinc-100 text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                                            }`}
                                        >
                                            {position.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-sm text-zinc-500 dark:text-zinc-400">
                                        {position.description || (
                                            <span className="italic opacity-50">
                                                No description
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission(
                                                'staff_positions.update',
                                            ) ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                    className="h-8 cursor-pointer border-zinc-200 px-3 text-xs shadow-sm hover:border-indigo-500 hover:text-indigo-600 dark:border-zinc-800 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                                                >
                                                    <Link
                                                        href={StaffPositionController.edit.url(
                                                            {
                                                                staff_position:
                                                                    position.id,
                                                            },
                                                        )}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}

                                            {hasPermission(
                                                'staff_positions.delete',
                                            ) ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Position"
                                                    description={`Are you sure you want to delete the position "${formatIdentifierLabel(position.name)}"? This action cannot be undone.`}
                                                    action={StaffPositionController.destroy.form(
                                                        {
                                                            staff_position:
                                                                position.id,
                                                        },
                                                    )}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Position "${formatIdentifierLabel(position.name)}" deleted successfully.`,
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
                                    colSpan={4}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No staff positions found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(positions) && positions.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            positions.prev_page_url ?? undefined
                                        }
                                    />
                                </PaginationItem>

                                {positions.links.map((link, idx) => {
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
                                            positions.next_page_url ?? undefined
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
