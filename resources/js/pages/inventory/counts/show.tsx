import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { type StockCountShowPageProps } from '@/types/stock-count';
import { Head, Link, router } from '@inertiajs/react';
import { toast } from 'sonner';

const labelize = (value: string): string =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function StockCountShow({
    stockCount,
}: StockCountShowPageProps) {
    const { hasPermission } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/inventory/dashboard' },
        { title: 'Stock Counts', href: '/stock-counts' },
        {
            title: stockCount.count_number,
            href: `/stock-counts/${stockCount.id}`,
        },
    ];

    const handlePost = () => {
        router.post(
            `/stock-counts/${stockCount.id}/post`,
            {},
            {
                onSuccess: () =>
                    toast.success(
                        'Stock count posted. Inventory balances updated.',
                    ),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Stock Count: ${stockCount.count_number}`} />

            <div className="m-4 max-w-7xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {stockCount.count_number}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {stockCount.inventory_location?.name ?? '-'}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/stock-counts">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div className="grid gap-4 md:grid-cols-4">
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Status
                            </span>
                            <div className="mt-1">
                                <Badge
                                    variant={
                                        stockCount.status === 'posted'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {labelize(stockCount.status)}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Count Date
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDate(stockCount.count_date)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Posted At
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDateTime(stockCount.posted_at)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Location
                            </span>
                            <p className="mt-1 font-medium">
                                {stockCount.inventory_location?.name ?? '-'}
                            </p>
                        </div>
                    </div>

                    {stockCount.notes ? (
                        <div className="mt-4 border-t pt-4">
                            <span className="text-sm text-muted-foreground">
                                Notes
                            </span>
                            <p className="mt-1">{stockCount.notes}</p>
                        </div>
                    ) : null}

                    {stockCount.status === 'draft' &&
                    hasPermission('stock_counts.update') ? (
                        <div className="mt-4 flex gap-2 border-t pt-4">
                            <Button size="sm" onClick={handlePost}>
                                Post Count
                            </Button>
                        </div>
                    ) : null}
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 className="mb-4 text-lg font-medium">Count Lines</h2>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Item</TableHead>
                                    <TableHead className="text-right">
                                        Expected
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Counted
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Variance
                                    </TableHead>
                                    <TableHead>Notes</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {stockCount.items?.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">
                                            {item.inventory_item?.generic_name ??
                                                item.inventory_item?.name ??
                                                '-'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {item.expected_quantity}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {item.counted_quantity}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {item.variance_quantity}
                                        </TableCell>
                                        <TableCell>{item.notes ?? '-'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
