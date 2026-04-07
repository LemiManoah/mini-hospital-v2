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
import { formatDateTime } from '@/lib/date';
import { type BreadcrumbItem } from '@/types';
import { type InventoryMovementReportPageProps } from '@/types/stock-movement';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function InventoryMovementReportIndex({
    movements,
    navigation,
    filters,
    movementTypes,
    locations,
}: InventoryMovementReportPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        { title: navigation.movements_title, href: navigation.movements_href },
    ];

    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? 'all');
    const [location, setLocation] = useState(filters.location ?? 'all');

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            type === (filters.type ?? 'all') &&
            location === (filters.location ?? 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                navigation.movements_href,
                {
                    search: search || undefined,
                    type: type === 'all' ? undefined : type,
                    location: location === 'all' ? undefined : location,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: [
                        'movements',
                        'filters',
                        'movementTypes',
                        'locations',
                    ],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [
        search,
        type,
        location,
        filters.search,
        filters.type,
        filters.location,
    ]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={navigation.movements_title} />

            <div className="m-4 flex flex-col gap-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {navigation.movements_title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Review posted inventory activity for the locations you
                        can access.
                    </p>
                </div>

                <div className="flex flex-col gap-4 md:flex-row">
                    <div className="w-full md:max-w-sm">
                        <Input
                            placeholder="Search by item or location..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                    <div className="w-full md:max-w-xs">
                        <SearchableSelect
                            options={[
                                { value: 'all', label: 'All movement types' },
                                ...movementTypes,
                            ]}
                            value={type}
                            onValueChange={setType}
                            placeholder="Filter by movement type"
                            emptyMessage="No movement types found."
                        />
                    </div>
                    <div className="w-full md:max-w-xs">
                        <SearchableSelect
                            options={[
                                { value: 'all', label: 'All locations' },
                                ...locations,
                            ]}
                            value={location}
                            onValueChange={setLocation}
                            placeholder="Filter by location"
                            emptyMessage="No locations found."
                        />
                    </div>
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>When</TableHead>
                                <TableHead>Item</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Batch</TableHead>
                                <TableHead>Expiry</TableHead>
                                <TableHead className="text-right">
                                    Quantity
                                </TableHead>
                                <TableHead className="text-right">
                                    Unit Cost
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {movements.data.length > 0 ? (
                                movements.data.map((movement) => (
                                    <TableRow key={movement.id}>
                                        <TableCell>
                                            {formatDateTime(
                                                movement.occurred_at,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {movement.item_name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {movement.location_name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {movement.movement_type_label ??
                                                '-'}
                                        </TableCell>
                                        <TableCell>
                                            {movement.batch_number ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {movement.expiry_date ?? '-'}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {movement.quantity.toFixed(3)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {movement.unit_cost !== null
                                                ? movement.unit_cost.toFixed(2)
                                                : '-'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        No stock movements found for the current
                                        filters.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {movements.links.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={
                                                movements.prev_page_url ??
                                                undefined
                                            }
                                        />
                                    </PaginationItem>
                                    {movements.links.map((link, index) => {
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
                                                movements.next_page_url ??
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
