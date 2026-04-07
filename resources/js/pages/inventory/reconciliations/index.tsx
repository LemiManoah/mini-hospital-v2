import { SearchableSelect } from '@/components/searchable-select';
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
import { formatDate, formatDateTime } from '@/lib/date';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type InventoryReconciliationIndexPageProps } from '@/types/inventory-reconciliation';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Reconciliations', href: '/reconciliations' },
];

const labelize = (value: string): string =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

const badgeVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' =>
    status === 'posted'
        ? 'default'
        : status === 'rejected'
          ? 'destructive'
          : 'secondary';

export default function InventoryReconciliationsIndex({
    reconciliations,
    filters,
    statusOptions,
}: InventoryReconciliationIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows = reconciliations.data ?? [];
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            status === (filters.status ?? 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/reconciliations',
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['reconciliations', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, status, filters.search, filters.status]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reconciliations" />

            <div className="m-4 flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="flex flex-1 flex-col gap-3 md:flex-row">
                        <div className="w-full md:max-w-sm">
                            <Input
                                placeholder="Search by reconciliation number or reason..."
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>
                        <div className="w-full md:max-w-xs">
                            <SearchableSelect
                                options={[
                                    { value: 'all', label: 'All statuses' },
                                    ...statusOptions,
                                ]}
                                value={status}
                                onValueChange={setStatus}
                                placeholder="Filter by status"
                                emptyMessage="No statuses found."
                            />
                        </div>
                    </div>
                    {hasPermission('stock_adjustments.create') ? (
                        <Button asChild>
                            <Link href="/reconciliations/create">
                                + New Reconciliation
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Location</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Posted At</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((reconciliation) => (
                                    <TableRow key={reconciliation.id}>
                                        <TableCell>
                                            {reconciliation.inventory_location
                                                ?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(
                                                reconciliation.adjustment_date,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {reconciliation.reason}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={badgeVariant(
                                                    reconciliation.workflow_status,
                                                )}
                                            >
                                                {labelize(
                                                    reconciliation.workflow_status,
                                                )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {formatDateTime(
                                                reconciliation.posted_at,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/reconciliations/${reconciliation.id}`}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        No reconciliations found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {reconciliations.links?.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                reconciliations.prev_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {reconciliations.links.map((link, idx) => {
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
                                    })}
                                    <PaginationItem>
                                        <PaginationNext
                                            href={
                                                reconciliations.next_page_url ??
                                                undefined
                                            }
                                        />
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
