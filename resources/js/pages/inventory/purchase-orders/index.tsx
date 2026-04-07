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
import { formatDate } from '@/lib/date';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type PurchaseOrderIndexPageProps } from '@/types/purchase-order';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Purchase Orders', href: '/purchase-orders' },
];

const statusColor = (status: string) => {
    const map: Record<
        string,
        'default' | 'secondary' | 'destructive' | 'outline'
    > = {
        draft: 'secondary',
        submitted: 'outline',
        approved: 'default',
        partial: 'outline',
        received: 'default',
        cancelled: 'destructive',
    };
    return map[status] ?? 'secondary';
};

const labelize = (value: string): string =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function PurchaseOrdersIndex({
    purchaseOrders,
    filters,
    statusOptions,
}: PurchaseOrderIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows = purchaseOrders.data ?? [];
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
                '/purchase-orders',
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['purchaseOrders', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, status, filters.search, filters.status]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Purchase Orders" />

            <div className="m-4 flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="flex flex-1 flex-col gap-3 md:flex-row">
                        <div className="w-full md:max-w-sm">
                            <Input
                                placeholder="Search by order number or supplier..."
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
                    {hasPermission('purchase_orders.create') ? (
                        <Button asChild>
                            <Link href="/purchase-orders/create">
                                + New Purchase Order
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Order #</TableHead>
                                <TableHead>Supplier</TableHead>
                                <TableHead>Order Date</TableHead>
                                <TableHead>Expected Delivery</TableHead>
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((po) => (
                                    <TableRow key={po.id}>
                                        <TableCell className="font-medium">
                                            {po.order_number}
                                        </TableCell>
                                        <TableCell>
                                            {po.supplier?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(po.order_date)}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(
                                                po.expected_delivery_date,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {Number(
                                                po.total_amount,
                                            ).toLocaleString(undefined, {
                                                minimumFractionDigits: 2,
                                            })}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={statusColor(po.status)}
                                            >
                                                {labelize(po.status)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/purchase-orders/${po.id}`}
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
                                        No purchase orders found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {purchaseOrders.links?.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                purchaseOrders.prev_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {purchaseOrders.links.map((link, idx) => {
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
                                                purchaseOrders.next_page_url ??
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
