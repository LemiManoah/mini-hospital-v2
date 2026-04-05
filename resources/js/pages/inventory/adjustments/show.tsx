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
import { type StockAdjustmentShowPageProps } from '@/types/stock-adjustment';
import { Head, Link, router } from '@inertiajs/react';
import { toast } from 'sonner';

const labelize = (value: string): string =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function StockAdjustmentShow({
    stockAdjustment,
}: StockAdjustmentShowPageProps) {
    const { hasPermission } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/inventory/dashboard' },
        { title: 'Stock Adjustments', href: '/stock-adjustments' },
        {
            title: stockAdjustment.adjustment_number,
            href: `/stock-adjustments/${stockAdjustment.id}`,
        },
    ];

    const handlePost = () => {
        router.post(
            `/stock-adjustments/${stockAdjustment.id}/post`,
            {},
            {
                onSuccess: () =>
                    toast.success(
                        'Stock adjustment posted. Inventory balances updated.',
                    ),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Stock Adjustment: ${stockAdjustment.adjustment_number}`} />

            <div className="m-4 max-w-7xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {stockAdjustment.adjustment_number}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {stockAdjustment.inventory_location?.name ?? '-'} |{' '}
                            {stockAdjustment.reason}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/stock-adjustments">Back</Link>
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
                                        stockAdjustment.status === 'posted'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {labelize(stockAdjustment.status)}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Adjustment Date
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDate(stockAdjustment.adjustment_date)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Posted At
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDateTime(stockAdjustment.posted_at)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Location
                            </span>
                            <p className="mt-1 font-medium">
                                {stockAdjustment.inventory_location?.name ?? '-'}
                            </p>
                        </div>
                    </div>

                    {stockAdjustment.notes ? (
                        <div className="mt-4 border-t pt-4">
                            <span className="text-sm text-muted-foreground">
                                Notes
                            </span>
                            <p className="mt-1">{stockAdjustment.notes}</p>
                        </div>
                    ) : null}

                    {stockAdjustment.status === 'draft' &&
                    hasPermission('stock_adjustments.update') ? (
                        <div className="mt-4 flex gap-2 border-t pt-4">
                            <Button size="sm" onClick={handlePost}>
                                Post Adjustment
                            </Button>
                        </div>
                    ) : null}
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 className="mb-4 text-lg font-medium">
                        Adjustment Lines
                    </h2>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Item</TableHead>
                                    <TableHead>Batch</TableHead>
                                    <TableHead>Expiry</TableHead>
                                    <TableHead className="text-right">
                                        Qty Delta
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Unit Cost
                                    </TableHead>
                                    <TableHead>Notes</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {stockAdjustment.items?.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">
                                            {item.inventory_item?.generic_name ??
                                                item.inventory_item?.name ??
                                                '-'}
                                        </TableCell>
                                        <TableCell>
                                            {item.inventory_batch?.batch_number ??
                                                item.batch_number ??
                                                '-'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(
                                                item.inventory_batch
                                                    ?.expiry_date ??
                                                    item.expiry_date,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {item.quantity_delta}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {item.unit_cost !== null
                                                ? Number(
                                                      item.unit_cost,
                                                  ).toLocaleString(
                                                      undefined,
                                                      {
                                                          minimumFractionDigits: 2,
                                                      },
                                                  )
                                                : '-'}
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
