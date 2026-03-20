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
import { type BreadcrumbItem } from '@/types';
import {
    type FacilityBranch,
    type FacilityBranchIndexPageProps,
} from '@/types/facility-branch';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Facility Branches', href: '/facility-branches' },
];

export default function FacilityBranchIndex({
    branches,
    filters,
}: FacilityBranchIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: FacilityBranch[] = Array.isArray(branches)
        ? branches
        : (branches.data ?? []);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/facility-branches',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['branches', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, filters.search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facility Branches" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1 sm:max-w-md">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Facility Branches
                    </h2>
                    <Input
                        placeholder="Search branches..."
                        className="mt-2"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                {hasPermission('facility_branches.create') ? (
                    <Button asChild className="shrink-0">
                        <Link href="/facility-branches/create">
                            + Add Branch
                        </Link>
                    </Button>
                ) : null}
            </div>

            <div className="m-2 overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <Table className="min-w-[980px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Branch</TableHead>
                            <TableHead>Code</TableHead>
                            <TableHead>Currency</TableHead>
                            <TableHead>Contact</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Flags</TableHead>
                            <TableHead>Staff</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length > 0 ? (
                            rows.map((branch) => (
                                <TableRow key={branch.id}>
                                    <TableCell>
                                        <div>
                                            <p className="font-medium">
                                                {branch.name}
                                            </p>
                                            {branch.email ? (
                                                <p className="text-xs text-muted-foreground">
                                                    {branch.email}
                                                </p>
                                            ) : null}
                                        </div>
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">
                                        {branch.branch_code}
                                    </TableCell>
                                    <TableCell>
                                        {branch.currency
                                            ? `${branch.currency.code} (${branch.currency.symbol})`
                                            : 'N/A'}
                                    </TableCell>
                                    <TableCell>
                                        {branch.main_contact ||
                                            branch.other_contact ||
                                            'N/A'}
                                    </TableCell>
                                    <TableCell>
                                        <span className="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-semibold uppercase">
                                            {branch.status}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-2 text-xs">
                                            {branch.is_main_branch ? (
                                                <span className="rounded-full bg-sky-100 px-2 py-1 text-sky-800 dark:bg-sky-950 dark:text-sky-200">
                                                    Main
                                                </span>
                                            ) : null}
                                            {branch.has_store ? (
                                                <span className="rounded-full bg-emerald-100 px-2 py-1 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                                                    Has Store
                                                </span>
                                            ) : null}
                                            {!branch.is_main_branch &&
                                            !branch.has_store ? (
                                                <span className="text-muted-foreground">
                                                    Standard
                                                </span>
                                            ) : null}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {branch.staff_count ?? 0}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            {hasPermission(
                                                'facility_branches.update',
                                            ) ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/facility-branches/${branch.id}/edit`}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}

                                            {hasPermission(
                                                'facility_branches.delete',
                                            ) ? (
                                                <DeleteConfirmationModal
                                                    title="Delete Branch"
                                                    description={`Are you sure you want to delete "${branch.name}"?`}
                                                    action={{
                                                        action: `/facility-branches/${branch.id}`,
                                                        method: 'delete',
                                                    }}
                                                    onSuccess={() =>
                                                        toast.success(
                                                            `Branch "${branch.name}" deleted successfully.`,
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            disabled={
                                                                branch.is_main_branch
                                                            }
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
                                    colSpan={8}
                                    className="py-12 text-center text-zinc-500 italic"
                                >
                                    No facility branches found.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {!Array.isArray(branches) && branches.links?.length > 3 ? (
                    <div className="mt-4">
                        <Pagination>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            branches.prev_page_url ?? undefined
                                        }
                                    />
                                </PaginationItem>

                                {branches.links.map((link, idx) => {
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
                                            branches.next_page_url ?? undefined
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
