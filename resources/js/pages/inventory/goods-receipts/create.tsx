import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import { type GoodsReceiptFormPageProps } from '@/types/goods-receipt';
import type { PurchaseOrder } from '@/types/purchase-order';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, LoaderCircle, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Goods Receipts', href: '/goods-receipts' },
    { title: 'Create', href: '/goods-receipts/create' },
];

interface ReceiptLine {
    purchase_order_item_id: string;
    inventory_item_id: string;
    item_label: string;
    quantity_remaining: number;
    quantity_received: string;
    unit_cost: string;
    batch_number: string;
    expiry_date: string;
    notes: string;
}

export default function GoodsReceiptCreate({
    purchaseOrders,
    selectedPurchaseOrder,
    inventoryLocations,
}: GoodsReceiptFormPageProps) {
    const [selectedPO, setSelectedPO] = useState<PurchaseOrder | null>(
        selectedPurchaseOrder,
    );

    const poOptions = purchaseOrders.map((po) => ({
        value: po.id,
        label: `${po.order_number} - ${po.supplier?.name ?? 'Unknown'}`,
    }));

    const locationOptions = inventoryLocations.map((loc) => ({
        value: loc.id,
        label: `${loc.name} (${loc.location_code})`,
    }));

    const buildLines = (po: PurchaseOrder | null): ReceiptLine[] => {
        if (!po?.items) {
            return [];
        }

        return po.items
            .filter(
                (item) =>
                    Number(item.quantity_received) <
                    Number(item.quantity_ordered),
            )
            .map((item) => ({
                purchase_order_item_id: item.id,
                inventory_item_id: item.inventory_item_id,
                item_label:
                    item.inventory_item?.generic_name ??
                    item.inventory_item?.name ??
                    '-',
                quantity_remaining:
                    Number(item.quantity_ordered) -
                    Number(item.quantity_received),
                quantity_received: '',
                unit_cost: item.unit_cost,
                batch_number: '',
                expiry_date: '',
                notes: '',
            }));
    };

    const form = useForm({
        purchase_order_id: selectedPO?.id ?? '',
        inventory_location_id: '',
        receipt_date: new Date().toISOString().split('T')[0],
        supplier_invoice_number: '',
        supplier_delivery_note: '',
        notes: '',
        items: buildLines(selectedPO),
    });

    const activeDraftReceipt =
        selectedPO?.goods_receipts?.find((receipt) => receipt.status === 'draft') ??
        null;

    const handlePOChange = (poId: string) => {
        const po = purchaseOrders.find((purchaseOrder) => purchaseOrder.id === poId) ?? null;

        setSelectedPO(po);
        form.setData({
            ...form.data,
            purchase_order_id: poId,
            items: buildLines(po),
        });
    };

    const updateLine = (
        index: number,
        field: keyof ReceiptLine,
        value: string,
    ) => {
        const updated = [...form.data.items];
        updated[index] = { ...updated[index], [field]: value };
        form.setData('items', updated);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/goods-receipts', {
            onSuccess: () =>
                toast.success('Goods receipt created successfully.'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Goods Receipt" />

            <div className="m-4 max-w-6xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Receive Goods
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Record items received against a purchase order.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            The receipt number will be generated automatically when you save.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/goods-receipts">Back</Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Purchase Order</Label>
                                <SearchableSelect
                                    options={poOptions}
                                    value={form.data.purchase_order_id}
                                    onValueChange={handlePOChange}
                                    placeholder="Select purchase order"
                                    emptyMessage="No receivable purchase orders."
                                />
                                <InputError
                                    message={form.errors.purchase_order_id}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Receiving Location</Label>
                                <SearchableSelect
                                    options={locationOptions}
                                    value={form.data.inventory_location_id}
                                    onValueChange={(value) =>
                                        form.setData(
                                            'inventory_location_id',
                                            value,
                                        )
                                    }
                                    placeholder="Select location"
                                    emptyMessage="No locations found."
                                />
                                <InputError
                                    message={form.errors.inventory_location_id}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="receipt_date">
                                    Receipt Date
                                </Label>
                                <Input
                                    id="receipt_date"
                                    type="date"
                                    value={form.data.receipt_date}
                                    onChange={(e) =>
                                        form.setData(
                                            'receipt_date',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={form.errors.receipt_date}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="supplier_invoice_number">
                                    Supplier Invoice #
                                </Label>
                                <Input
                                    id="supplier_invoice_number"
                                    value={form.data.supplier_invoice_number}
                                    onChange={(e) =>
                                        form.setData(
                                            'supplier_invoice_number',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={
                                        form.errors.supplier_invoice_number
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="supplier_delivery_note">
                                    Delivery Note #
                                </Label>
                                <Input
                                    id="supplier_delivery_note"
                                    value={form.data.supplier_delivery_note}
                                    onChange={(e) =>
                                        form.setData(
                                            'supplier_delivery_note',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={
                                        form.errors.supplier_delivery_note
                                    }
                                />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    rows={2}
                                    value={form.data.notes}
                                    onChange={(e) =>
                                        form.setData('notes', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.notes} />
                            </div>
                        </div>
                    </div>

                    {activeDraftReceipt ? (
                        <Alert className="border-amber-200 bg-amber-50 text-amber-950">
                            <AlertTriangle />
                            <AlertTitle>Draft receipt already exists</AlertTitle>
                            <AlertDescription>
                                <p>
                                    This purchase order already has draft receipt{' '}
                                    <strong>{activeDraftReceipt.receipt_number}</strong>.
                                    Post that receipt before creating another one.
                                </p>
                                <Button variant="outline" size="sm" asChild className="mt-2">
                                    <Link href={`/goods-receipts/${activeDraftReceipt.id}`}>
                                        Open Draft Receipt
                                    </Link>
                                </Button>
                            </AlertDescription>
                        </Alert>
                    ) : null}

                    {form.data.items.length > 0 ? (
                        <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <h2 className="mb-4 text-lg font-medium">
                                Items to Receive
                            </h2>
                            <InputError message={form.errors.items} />

                            <div className="overflow-x-auto">
                                <Table className="min-w-[900px]">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Item</TableHead>
                                            <TableHead className="w-24 text-right">
                                                Remaining
                                            </TableHead>
                                            <TableHead className="w-28">
                                                Qty Received
                                            </TableHead>
                                            <TableHead className="w-28">
                                                Unit Cost
                                            </TableHead>
                                            <TableHead className="w-28">
                                                Batch #
                                            </TableHead>
                                            <TableHead className="w-36">
                                                Expiry Date
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {form.data.items.map((line, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">
                                                    {line.item_label}
                                                </TableCell>
                                                <TableCell className="text-right text-muted-foreground">
                                                    {line.quantity_remaining.toFixed(
                                                        3,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        step="any"
                                                        min="0"
                                                        max={
                                                            line.quantity_remaining
                                                        }
                                                        value={
                                                            line.quantity_received
                                                        }
                                                        onChange={(e) =>
                                                            updateLine(
                                                                index,
                                                                'quantity_received',
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            form.errors[
                                                                `items.${index}.quantity_received` as keyof typeof form.errors
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        step="any"
                                                        min="0"
                                                        value={line.unit_cost}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                index,
                                                                'unit_cost',
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        value={
                                                            line.batch_number
                                                        }
                                                        onChange={(e) =>
                                                            updateLine(
                                                                index,
                                                                'batch_number',
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="Batch"
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="date"
                                                        value={
                                                            line.expiry_date
                                                        }
                                                        onChange={(e) =>
                                                            updateLine(
                                                                index,
                                                                'expiry_date',
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    ) : null}

                    <div className="flex gap-3">
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                form.data.items.length === 0 ||
                                activeDraftReceipt !== null
                            }
                        >
                            {form.processing ? (
                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <PlusCircle className="mr-2 h-4 w-4" />
                            )}
                            Create Goods Receipt
                        </Button>
                        <Button variant="ghost" type="button" asChild>
                            <Link href="/goods-receipts">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
