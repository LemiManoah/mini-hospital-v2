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
import { type GoodsReceiptIndexPageProps } from '@/types/goods-receipt';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const labelize = (value: string): string =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function GoodsReceiptsIndex({
    goodsReceipts,
    navigation,
    filters,
    statusOptions,
}: GoodsReceiptIndexPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        { title: navigation.receipts_title, href: navigation.receipts_href },
    ];

    const { hasPermission } = usePermissions();
    const rows = goodsReceipts.data ?? [];
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
                navigation.receipts_href,
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['goodsReceipts', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, status, filters.search, filters.status]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={navigation.receipts_title} />

            <div className="m-4 flex flex-col gap-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {navigation.receipts_title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Review received stock and open draft receipts for the
                        locations you manage.
                    </p>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="flex flex-1 flex-col gap-3 md:flex-row">
                        <div className="w-full md:max-w-sm">
                            <Input
                                placeholder="Search by receipt or invoice number..."
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
                    {hasPermission('goods_receipts.create') ? (
                        <Button asChild>
                            <Link href={`${navigation.receipts_href}/create`}>
                                + New Goods Receipt
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Receipt #</TableHead>
                                <TableHead>PO #</TableHead>
                                <TableHead>Supplier</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead>Receipt Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((gr) => (
                                    <TableRow key={gr.id}>
                                        <TableCell className="font-medium">
                                            {gr.receipt_number}
                                        </TableCell>
                                        <TableCell>
                                            {gr.purchase_order?.order_number ??
                                                '-'}
                                        </TableCell>
                                        <TableCell>
                                            {gr.purchase_order?.supplier
                                                ?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {gr.inventory_location?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(gr.receipt_date)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    gr.status === 'posted'
                                                        ? 'default'
                                                        : gr.status ===
                                                            'cancelled'
                                                          ? 'destructive'
                                                          : 'secondary'
                                                }
                                            >
                                                {labelize(gr.status)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`${navigation.receipts_href}/${gr.id}`}
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
                                        No goods receipts found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {goodsReceipts.links?.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                goodsReceipts.prev_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {goodsReceipts.links.map((link, idx) => {
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
                                                goodsReceipts.next_page_url ??
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
