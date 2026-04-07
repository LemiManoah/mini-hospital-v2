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
import { formatDate } from '@/lib/date';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type GoodsReceiptShowPageProps } from '@/types/goods-receipt';
import { Head, Link, router } from '@inertiajs/react';

const labelize = (value: string): string =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function GoodsReceiptShow({
    navigation,
    goodsReceipt: gr,
}: GoodsReceiptShowPageProps) {
    const { hasPermission } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        { title: navigation.receipts_title, href: navigation.receipts_href },
        {
            title: gr.receipt_number,
            href: `${navigation.receipts_href}/${gr.id}`,
        },
    ];

    const handlePost = () => {
        router.post(`${navigation.receipts_href}/${gr.id}/post`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`${navigation.receipts_title}: ${gr.receipt_number}`}
            />

            <div className="m-4 max-w-6xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {gr.receipt_number}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            PO: {gr.purchase_order?.order_number ?? '-'} |
                            Supplier: {gr.purchase_order?.supplier?.name ?? '-'}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <a
                                href={`${navigation.receipts_href}/${gr.id}/print`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                Print
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={navigation.receipts_href}>Back</Link>
                        </Button>
                    </div>
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
                                        gr.status === 'posted'
                                            ? 'default'
                                            : gr.status === 'cancelled'
                                              ? 'destructive'
                                              : 'secondary'
                                    }
                                >
                                    {labelize(gr.status)}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Receipt Date
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDate(gr.receipt_date)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Location
                            </span>
                            <p className="mt-1 font-medium">
                                {gr.inventory_location?.name ?? '-'}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Invoice #
                            </span>
                            <p className="mt-1 font-medium">
                                {gr.supplier_invoice_number ?? '-'}
                            </p>
                        </div>
                    </div>
                    {gr.notes ? (
                        <div className="mt-4 border-t pt-4">
                            <span className="text-sm text-muted-foreground">
                                Notes
                            </span>
                            <p className="mt-1">{gr.notes}</p>
                        </div>
                    ) : null}

                    {gr.status === 'draft' &&
                    hasPermission('goods_receipts.update') ? (
                        <div className="mt-4 flex gap-2 border-t pt-4">
                            <Button size="sm" onClick={handlePost}>
                                Post Receipt
                            </Button>
                        </div>
                    ) : null}
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 className="mb-4 text-lg font-medium">Received Items</h2>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Item</TableHead>
                                    <TableHead className="text-right">
                                        Qty Received
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Unit Cost
                                    </TableHead>
                                    <TableHead>Batch #</TableHead>
                                    <TableHead>Expiry Date</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {gr.items?.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">
                                            {item.inventory_item
                                                ?.generic_name ??
                                                item.inventory_item?.name ??
                                                '-'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {item.quantity_received}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {Number(
                                                item.unit_cost,
                                            ).toLocaleString(undefined, {
                                                minimumFractionDigits: 2,
                                            })}
                                        </TableCell>
                                        <TableCell>
                                            {item.batch_number ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(item.expiry_date)}
                                        </TableCell>
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
