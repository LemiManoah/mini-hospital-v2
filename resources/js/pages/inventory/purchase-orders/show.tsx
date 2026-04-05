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
import { type PurchaseOrderShowPageProps } from '@/types/purchase-order';
import { Head, Link, router } from '@inertiajs/react';

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

export default function PurchaseOrderShow({
    purchaseOrder: po,
}: PurchaseOrderShowPageProps) {
    const { hasPermission } = usePermissions();
    const draftGoodsReceipt =
        po.goods_receipts?.find((receipt) => receipt.status === 'draft') ??
        null;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/inventory/dashboard' },
        { title: 'Purchase Orders', href: '/purchase-orders' },
        { title: po.order_number, href: `/purchase-orders/${po.id}` },
    ];

    const postAction = (action: string) => {
        router.post(`/purchase-orders/${po.id}/${action}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`PO: ${po.order_number}`} />

            <div className="m-4 max-w-6xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {po.order_number}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Supplier: {po.supplier?.name ?? '-'}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/purchase-orders">Back</Link>
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
                                <Badge variant={statusColor(po.status)}>
                                    {labelize(po.status)}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Order Date
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDate(po.order_date)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Expected Delivery
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDate(po.expected_delivery_date)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Total Amount
                            </span>
                            <p className="mt-1 text-lg font-semibold">
                                {Number(po.total_amount).toLocaleString(
                                    undefined,
                                    { minimumFractionDigits: 2 },
                                )}
                            </p>
                        </div>
                    </div>
                    {po.notes ? (
                        <div className="mt-4 border-t pt-4">
                            <span className="text-sm text-muted-foreground">
                                Notes
                            </span>
                            <p className="mt-1">{po.notes}</p>
                        </div>
                    ) : null}

                    {hasPermission('purchase_orders.update') ? (
                        <div className="mt-4 flex gap-2 border-t pt-4">
                            {po.status === 'draft' ? (
                                <>
                                    <Button size="sm" asChild>
                                        <Link
                                            href={`/purchase-orders/${po.id}/edit`}
                                        >
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => postAction('submit')}
                                    >
                                        Submit for Approval
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => postAction('cancel')}
                                    >
                                        Cancel PO
                                    </Button>
                                </>
                            ) : null}
                            {po.status === 'submitted' ? (
                                <>
                                    <Button
                                        size="sm"
                                        onClick={() => postAction('approve')}
                                    >
                                        Approve
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => postAction('cancel')}
                                    >
                                        Cancel PO
                                    </Button>
                                </>
                            ) : null}
                            {po.status === 'approved' ||
                            po.status === 'partial' ? (
                                draftGoodsReceipt ? (
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href={`/goods-receipts/${draftGoodsReceipt.id}`}
                                        >
                                            Open Draft Receipt
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button size="sm" asChild>
                                        <Link
                                            href={`/goods-receipts/create?purchase_order_id=${po.id}`}
                                        >
                                            Receive Goods
                                        </Link>
                                    </Button>
                                )
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 className="mb-4 text-lg font-medium">Line Items</h2>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Item</TableHead>
                                    <TableHead className="text-right">
                                        Qty Ordered
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Unit Cost
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Total
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Qty Received
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Remaining
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {po.items?.map((item) => {
                                    const remaining = Math.max(
                                        0,
                                        Number(item.quantity_ordered) -
                                            Number(item.quantity_received),
                                    );
                                    return (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-medium">
                                                {item.inventory_item
                                                    ?.generic_name ??
                                                    item.inventory_item?.name ??
                                                    '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {item.quantity_ordered}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {Number(
                                                    item.unit_cost,
                                                ).toLocaleString(undefined, {
                                                    minimumFractionDigits: 2,
                                                })}
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                {Number(
                                                    item.total_cost,
                                                ).toLocaleString(undefined, {
                                                    minimumFractionDigits: 2,
                                                })}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {item.quantity_received}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {remaining > 0 ? (
                                                    <span className="text-yellow-600">
                                                        {remaining.toFixed(3)}
                                                    </span>
                                                ) : (
                                                    <span className="text-green-600">
                                                        0
                                                    </span>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                {po.goods_receipts && po.goods_receipts.length > 0 ? (
                    <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 className="mb-4 text-lg font-medium">
                            Goods Receipts
                        </h2>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Receipt #</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {po.goods_receipts.map((gr) => (
                                        <TableRow key={gr.id}>
                                            <TableCell className="font-medium">
                                                {gr.receipt_number}
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
                                                        href={`/goods-receipts/${gr.id}`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
