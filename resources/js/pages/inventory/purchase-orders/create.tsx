import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PurchaseOrderFormPageProps } from '@/types/purchase-order';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, PlusCircle, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Purchase Orders', href: '/purchase-orders' },
    { title: 'Create', href: '/purchase-orders/create' },
];

interface LineItem {
    inventory_item_id: string;
    quantity_ordered: string;
    unit_cost: string;
}

export default function PurchaseOrderCreate({
    suppliers,
    inventoryItems,
}: PurchaseOrderFormPageProps) {
    const form = useForm({
        supplier_id: '',
        order_date: new Date().toISOString().split('T')[0],
        expected_delivery_date: '',
        notes: '',
        items: [
            { inventory_item_id: '', quantity_ordered: '', unit_cost: '' },
        ] as LineItem[],
    });

    const supplierOptions = suppliers.map((s) => ({
        value: s.id,
        label: s.name,
    }));

    const itemOptions = inventoryItems.map((item) => ({
        value: item.id,
        label: item.generic_name ?? item.name,
    }));

    const addLine = () => {
        form.setData('items', [
            ...form.data.items,
            { inventory_item_id: '', quantity_ordered: '', unit_cost: '' },
        ]);
    };

    const removeLine = (index: number) => {
        form.setData(
            'items',
            form.data.items.filter((_, i) => i !== index),
        );
    };

    const updateLine = (
        index: number,
        field: keyof LineItem,
        value: string,
    ) => {
        const updated = [...form.data.items];
        updated[index] = { ...updated[index], [field]: value };
        form.setData('items', updated);
    };

    const lineTotal = (line: LineItem) => {
        const qty = parseFloat(line.quantity_ordered) || 0;
        const cost = parseFloat(line.unit_cost) || 0;
        return (qty * cost).toFixed(2);
    };

    const grandTotal = form.data.items.reduce(
        (sum, line) => sum + (parseFloat(lineTotal(line)) || 0),
        0,
    );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/purchase-orders');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Purchase Order" />

            <div className="m-4 max-w-6xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Purchase Order
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Raise a new purchase order for a supplier.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            The purchase order number will be generated
                            automatically when you save.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/purchase-orders">Back</Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Supplier</Label>
                                <SearchableSelect
                                    options={supplierOptions}
                                    value={form.data.supplier_id}
                                    onValueChange={(v) =>
                                        form.setData('supplier_id', v)
                                    }
                                    placeholder="Select supplier"
                                    emptyMessage="No suppliers found."
                                />
                                <InputError message={form.errors.supplier_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="order_date">Order Date</Label>
                                <Input
                                    id="order_date"
                                    type="date"
                                    value={form.data.order_date}
                                    onChange={(e) =>
                                        form.setData(
                                            'order_date',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError message={form.errors.order_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="expected_delivery_date">
                                    Expected Delivery Date
                                </Label>
                                <Input
                                    id="expected_delivery_date"
                                    type="date"
                                    value={form.data.expected_delivery_date}
                                    onChange={(e) =>
                                        form.setData(
                                            'expected_delivery_date',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.expected_delivery_date}
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

                    <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-medium">Line Items</h2>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addLine}
                            >
                                <PlusCircle className="mr-1 h-4 w-4" />
                                Add Line
                            </Button>
                        </div>
                        <InputError message={form.errors.items} />

                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="min-w-[250px]">
                                            Item
                                        </TableHead>
                                        <TableHead className="w-32">
                                            Quantity
                                        </TableHead>
                                        <TableHead className="w-32">
                                            Unit Cost
                                        </TableHead>
                                        <TableHead className="w-32 text-right">
                                            Total
                                        </TableHead>
                                        <TableHead className="w-16" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {form.data.items.map((line, index) => (
                                        <TableRow key={index}>
                                            <TableCell>
                                                <SearchableSelect
                                                    options={itemOptions}
                                                    value={
                                                        line.inventory_item_id
                                                    }
                                                    onValueChange={(v) =>
                                                        updateLine(
                                                            index,
                                                            'inventory_item_id',
                                                            v,
                                                        )
                                                    }
                                                    placeholder="Select item"
                                                    emptyMessage="No items found."
                                                />
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `items.${index}.inventory_item_id` as keyof typeof form.errors
                                                        ]
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    value={
                                                        line.quantity_ordered
                                                    }
                                                    onChange={(e) =>
                                                        updateLine(
                                                            index,
                                                            'quantity_ordered',
                                                            e.target.value,
                                                        )
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
                                            <TableCell className="text-right font-medium">
                                                {lineTotal(line)}
                                            </TableCell>
                                            <TableCell>
                                                {form.data.items.length > 1 ? (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            removeLine(index)
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4 text-red-500" />
                                                    </Button>
                                                ) : null}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="mt-4 flex justify-end border-t pt-4">
                            <div className="text-right">
                                <span className="text-sm text-muted-foreground">
                                    Grand Total:
                                </span>
                                <span className="ml-2 text-lg font-semibold">
                                    {grandTotal.toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                    })}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <PlusCircle className="mr-2 h-4 w-4" />
                            )}
                            Create Purchase Order
                        </Button>
                        <Button variant="ghost" type="button" asChild>
                            <Link href="/purchase-orders">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
