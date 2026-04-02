import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    type InventoryItem,
    type InventoryItemIndexPageProps,
} from '@/types/inventory-item';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Items', href: '/inventory-items' },
];

const labelize = (value: string | null): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';

export default function InventoryItemsIndex({
    items,
    filters,
    itemTypes,
}: InventoryItemIndexPageProps) {
    const { hasPermission } = usePermissions();
    const rows: InventoryItem[] = Array.isArray(items) ? items : (items.data ?? []);
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
                '/inventory-items',
                {
                    search: search || undefined,
                    type: type === 'all' ? undefined : type,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['items', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [search, type, filters.search, filters.type]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventory Items" />

            <div className="m-4 flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="flex flex-1 flex-col gap-3 md:flex-row">
                        <div className="w-full md:max-w-sm">
                            <Input
                                placeholder="Search name, generic name, brand, or manufacturer..."
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>
                        <div className="w-full md:max-w-xs">
                            <Select value={type} onValueChange={setType}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Filter by type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All types</SelectItem>
                                    {itemTypes.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    {hasPermission('inventory_items.create') ? (
                        <Button asChild>
                            <Link href="/inventory-items/create">
                                + Add Inventory Item
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <div className="overflow-x-auto rounded border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <Table className="min-w-[1080px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Item</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Drug Details</TableHead>
                                <TableHead>Unit</TableHead>
                                <TableHead>Expiry</TableHead>
                                <TableHead>Thresholds</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {item.generic_name ??
                                                        item.name}
                                                </span>
                                                {item.brand_name ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        {item.brand_name}
                                                    </span>
                                                ) : null}
                                                {item.manufacturer ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        {item.manufacturer}
                                                    </span>
                                                ) : null}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {labelize(item.item_type)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {item.item_type === 'drug'
                                                ? [
                                                      item.category
                                                          ? labelize(
                                                                item.category,
                                                            )
                                                          : null,
                                                      item.dosage_form
                                                          ? labelize(
                                                                item.dosage_form,
                                                            )
                                                          : null,
                                                      item.strength,
                                                  ]
                                                      .filter(Boolean)
                                                      .join(' | ')
                                                : 'Not applicable'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {item.unit
                                                ? `${item.unit.name} (${item.unit.symbol})`
                                                : 'Not set'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    item.expires
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {item.expires
                                                    ? 'Expires'
                                                    : 'Non-expiring'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            Min {item.minimum_stock_level} /
                                            Reorder {item.reorder_level}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    item.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {item.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                {hasPermission(
                                                    'inventory_items.update',
                                                ) ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/inventory-items/${item.id}/edit`}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                ) : null}
                                                {hasPermission(
                                                    'inventory_items.delete',
                                                ) ? (
                                                    <DeleteConfirmationModal
                                                        title="Delete Inventory Item"
                                                        description={`Are you sure you want to delete "${item.generic_name ?? item.name}"?`}
                                                        action={{
                                                            action: `/inventory-items/${item.id}`,
                                                            method: 'delete',
                                                        }}
                                                        onSuccess={() =>
                                                            toast.success(
                                                                `Inventory item "${item.generic_name ?? item.name}" deleted successfully.`,
                                                            )
                                                        }
                                                        trigger={
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
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
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        No inventory items found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {!Array.isArray(items) && items.links?.length > 3 ? (
                        <div className="mt-4">
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            href={items.prev_page_url ?? undefined}
                                        />
                                    </PaginationItem>
                                    {items.links.map((link, idx) => {
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
                                            href={items.next_page_url ?? undefined}
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
