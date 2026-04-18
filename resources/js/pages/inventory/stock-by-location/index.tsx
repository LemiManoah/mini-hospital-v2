import { SearchableSelect } from '@/components/searchable-select';
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
import { type InventoryStockByLocationPageProps } from '@/types/inventory-stock';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const labelize = (value: string | null): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : '-';

export default function InventoryStockByLocationIndex({
    rows,
    navigation,
    filters,
    itemTypes,
    locations,
    note: _note,
}: InventoryStockByLocationPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        ...(navigation.management_href
            ? [
                  {
                      title: navigation.management_title ?? 'Management',
                      href: navigation.management_href,
                  },
              ]
            : []),
        { title: navigation.stock_title, href: navigation.stock_href },
    ];

    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? 'all');

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            type === (filters.type ?? 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                navigation.stock_href,
                {
                    search: search || undefined,
                    type: type === 'all' ? undefined : type,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['rows', 'filters', 'itemTypes', 'locations'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [filters.search, filters.type, navigation.stock_href, search, type]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={navigation.stock_title} />

            <div className="m-4 flex flex-col gap-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {navigation.stock_title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        View total on-hand stock across the locations available
                        in this workspace.
                    </p>
                </div>

                <div className="flex flex-col gap-4 md:flex-row">
                    <div className="w-full md:max-w-sm">
                        <Input
                            placeholder="Search by item..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                    <div className="w-full md:max-w-xs">
                        <SearchableSelect
                            options={[
                                { value: 'all', label: 'All item types' },
                                ...itemTypes,
                            ]}
                            value={type}
                            onValueChange={setType}
                            placeholder="Filter by type"
                            emptyMessage="No item types found."
                        />
                    </div>
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Item</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Unit</TableHead>
                                {locations.map((location) => (
                                    <TableHead
                                        key={location.id}
                                        className="min-w-40 text-right"
                                    >
                                        <div>{location.name}</div>
                                        <div className="text-xs font-normal text-muted-foreground">
                                            {location.code} -{' '}
                                            {labelize(location.type)}
                                        </div>
                                    </TableHead>
                                ))}
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.data.length > 0 ? (
                                rows.data.map((row) => (
                                    <TableRow key={row.item_id}>
                                        <TableCell className="font-medium">
                                            {row.item_name}
                                        </TableCell>
                                        <TableCell>
                                            {labelize(row.item_type)}
                                        </TableCell>
                                        <TableCell>
                                            {row.unit ?? 'No unit'}
                                        </TableCell>
                                        {locations.map((location) => (
                                            <TableCell
                                                key={`${row.item_id}:${location.id}`}
                                                className="text-right font-medium"
                                            >
                                                {(
                                                    row.location_quantities[
                                                        location.id
                                                    ] ?? 0
                                                ).toFixed(3)}
                                            </TableCell>
                                        ))}
                                        <TableCell className="text-right font-semibold">
                                            {row.total_quantity.toFixed(3)}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={locations.length + 4}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        No stock items found for the current
                                        filters.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {rows.links.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                rows.prev_page_url ?? undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {rows.links.map((link, index) => {
                                        const label = link.label
                                            .replace(/<[^>]*>/g, '')
                                            .trim();

                                        if (label === '...') {
                                            return (
                                                <PaginationItem
                                                    key={`ellipsis-${index}`}
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
                                                rows.next_page_url ?? undefined
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
