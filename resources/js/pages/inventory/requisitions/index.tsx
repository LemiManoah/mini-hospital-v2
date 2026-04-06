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
import { type InventoryRequisitionIndexPageProps } from '@/types/inventory-requisition';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const badgeVariant = (
    status: string | null,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'fulfilled') {
        return 'default';
    }

    if (status === 'rejected' || status === 'cancelled') {
        return 'destructive';
    }

    if (status === 'approved' || status === 'partially_issued') {
        return 'outline';
    }

    return 'secondary';
};

export default function InventoryRequisitionsIndex({
    requisitions,
    navigation,
    filters,
    statusOptions,
}: InventoryRequisitionIndexPageProps) {
    const isRequesterWorkspace = navigation.key !== 'inventory';
    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        {
            title: navigation.requisitions_title,
            href: navigation.requisitions_href,
        },
    ];

    const { hasPermission } = usePermissions();
    const rows = requisitions.data ?? [];
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
                navigation.requisitions_href,
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['requisitions', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [filters.search, filters.status, search, status]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={navigation.requisitions_title} />

            <div className="m-4 flex flex-col gap-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {navigation.requisitions_title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {isRequesterWorkspace
                            ? 'Track stock requests raised from this unit to the main store, then follow them through approval and issue.'
                            : 'Review incoming requisitions from pharmacy and laboratory, then approve, reject, or issue stock from the main store.'}
                    </p>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="flex flex-1 flex-col gap-3 md:flex-row">
                        <div className="w-full md:max-w-sm">
                            <Input
                                placeholder={
                                    isRequesterWorkspace
                                        ? 'Search by requisition number or main store...'
                                        : 'Search by requisition number or requesting unit...'
                                }
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

                    {isRequesterWorkspace &&
                    hasPermission('inventory_requisitions.create') ? (
                        <Button asChild>
                            <Link href={`${navigation.requisitions_href}/create`}>
                                + New Requisition
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Requisition #</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>
                                    {isRequesterWorkspace
                                        ? 'Requesting Unit'
                                        : 'Fulfilling Store'}
                                </TableHead>
                                <TableHead>
                                    {isRequesterWorkspace
                                        ? 'Fulfilling Store'
                                        : 'Requesting Unit'}
                                </TableHead>
                                <TableHead>Priority</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Issued At</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((requisition) => (
                                    <TableRow key={requisition.id}>
                                        <TableCell className="font-medium">
                                            {requisition.requisition_number}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(
                                                requisition.requisition_date,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {requisition.requesting_location
                                                ?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {requisition.fulfilling_location
                                                ?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {requisition.priority_label ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={badgeVariant(
                                                    requisition.status,
                                                )}
                                            >
                                                {requisition.status_label ?? '-'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {formatDateTime(
                                                requisition.issued_at,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`${navigation.requisitions_href}/${requisition.id}`}
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
                                        colSpan={8}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        {isRequesterWorkspace
                                            ? 'No requisitions found for this unit.'
                                            : 'No incoming requisitions found for the main store.'}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {requisitions.links?.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                requisitions.prev_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {requisitions.links.map((link, idx) => {
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
                                                requisitions.next_page_url ??
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
