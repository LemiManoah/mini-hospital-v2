import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { type InventoryReconciliationFormPageProps } from '@/types/inventory-reconciliation';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, LoaderCircle, PlusCircle, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Reconciliations', href: '/reconciliations' },
    { title: 'Create', href: '/reconciliations/create' },
];

type ReconciliationLine = {
    inventory_item_id: string;
    inventory_batch_id: string;
    actual_quantity: string;
    unit_cost: string;
    batch_number: string;
    expiry_date: string;
    notes: string;
};

const emptyLine = (): ReconciliationLine => ({
    inventory_item_id: '',
    inventory_batch_id: '',
    actual_quantity: '',
    unit_cost: '',
    batch_number: '',
    expiry_date: '',
    notes: '',
});

export default function InventoryReconciliationCreate({
    inventoryLocations,
    inventoryItems,
    locationBalances,
    batchBalances,
}: InventoryReconciliationFormPageProps) {
    const form = useForm({
        inventory_location_id: '',
        reconciliation_date: new Date().toISOString().split('T')[0],
        reason: '',
        notes: '',
        items: [emptyLine()],
    });

    const locationOptions = inventoryLocations.map((location) => ({
        value: location.id,
        label: `${location.name} (${location.location_code})`,
    }));

    const locationBalanceFor = (inventoryItemId: string): number => {
        const balance = locationBalances.find(
            (entry) =>
                entry.inventory_location_id ===
                    form.data.inventory_location_id &&
                entry.inventory_item_id === inventoryItemId,
        );

        return balance?.quantity ?? 0;
    };

    const itemOptions = inventoryItems.map((item) => ({
        value: item.id,
        label: `${item.generic_name ?? item.name} | Qty ${locationBalanceFor(item.id).toFixed(3)}`,
    }));

    const batchOptionsFor = (inventoryItemId: string) =>
        batchBalances
            .filter(
                (entry) =>
                    entry.inventory_location_id ===
                        form.data.inventory_location_id &&
                    entry.inventory_item_id === inventoryItemId,
            )
            .map((entry) => ({
                value: entry.inventory_batch_id,
                label: `${entry.batch_number ?? 'No batch'} | Qty ${entry.quantity.toFixed(3)}${entry.expiry_date ? ` | Exp ${entry.expiry_date}` : ''}`,
            }));

    const updateLine = (
        index: number,
        field: keyof ReconciliationLine,
        value: string,
    ) => {
        const updated = [...form.data.items];
        updated[index] = { ...updated[index], [field]: value };
        form.setData('items', updated);
    };

    const replaceLine = (
        index: number,
        updater: (line: ReconciliationLine) => ReconciliationLine,
    ) => {
        const updated = [...form.data.items];
        updated[index] = updater(updated[index]);
        form.setData('items', updated);
    };

    const addLine = () => {
        form.setData('items', [...form.data.items, emptyLine()]);
    };

    const removeLine = (index: number) => {
        if (form.data.items.length === 1) {
            form.setData('items', [emptyLine()]);

            return;
        }

        form.setData(
            'items',
            form.data.items.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/reconciliations');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Reconciliation" />

            <div className="m-4 max-w-7xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Reconciliation
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Record the actual quantity found for each item in
                            one location. The system will calculate the variance
                            for review, approval, and posting.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            The reconciliation number will be generated
                            automatically when you save.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/reconciliations">Back</Link>
                    </Button>
                </div>

                <Alert>
                    <AlertTriangle className="h-4 w-4" />
                    <AlertTitle>How to use this form</AlertTitle>
                    <AlertDescription>
                        Enter the actual quantity you found. If the actual
                        quantity is lower than the system quantity, choose the
                        batch to reduce. If it is higher, you can capture new
                        batch details for the stock being added.
                    </AlertDescription>
                </Alert>

                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Location</Label>
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
                                <Label htmlFor="reconciliation_date">
                                    Reconciliation Date
                                </Label>
                                <Input
                                    id="reconciliation_date"
                                    type="date"
                                    value={form.data.reconciliation_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'reconciliation_date',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={form.errors.reconciliation_date}
                                />
                            </div>

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="reason">Reason</Label>
                                <Input
                                    id="reason"
                                    value={form.data.reason}
                                    onChange={(event) =>
                                        form.setData(
                                            'reason',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Cycle count, damage, expiry, shelf verification, opening balance..."
                                    required
                                />
                                <InputError message={form.errors.reason} />
                            </div>

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    rows={2}
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData(
                                            'notes',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.notes} />
                            </div>
                        </div>
                    </div>

                    <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-medium">
                                    Reconciliation Lines
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Each line compares one item&apos;s system
                                    quantity with what you actually found in the
                                    selected location.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={addLine}
                            >
                                <PlusCircle className="mr-2 h-4 w-4" />
                                Add Line
                            </Button>
                        </div>

                        <InputError message={form.errors.items} />

                        <div className="overflow-x-auto">
                            <Table className="min-w-[1280px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-56">
                                            Item
                                        </TableHead>
                                        <TableHead className="w-28 text-right">
                                            System Qty
                                        </TableHead>
                                        <TableHead className="w-28 text-right">
                                            Actual Qty
                                        </TableHead>
                                        <TableHead className="w-28 text-right">
                                            Variance
                                        </TableHead>
                                        <TableHead className="w-48">
                                            Batch To Reconcile
                                        </TableHead>
                                        <TableHead className="w-28">
                                            Unit Cost
                                        </TableHead>
                                        <TableHead className="w-36">
                                            New Batch #
                                        </TableHead>
                                        <TableHead className="w-36">
                                            Expiry Date
                                        </TableHead>
                                        <TableHead>Notes</TableHead>
                                        <TableHead className="w-16" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {form.data.items.map((line, index) => {
                                        const systemQty = locationBalanceFor(
                                            line.inventory_item_id,
                                        );
                                        const actualQty = Number(
                                            line.actual_quantity || 0,
                                        );
                                        const variance = actualQty - systemQty;
                                        const isLoss = variance < 0;
                                        const availableBatchOptions =
                                            batchOptionsFor(
                                                line.inventory_item_id,
                                            );

                                        return (
                                            <TableRow key={index}>
                                                <TableCell className="align-top">
                                                    <SearchableSelect
                                                        options={itemOptions}
                                                        value={
                                                            line.inventory_item_id
                                                        }
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            replaceLine(
                                                                index,
                                                                (
                                                                    currentLine,
                                                                ) => {
                                                                    const item =
                                                                        inventoryItems.find(
                                                                            (
                                                                                inventoryItem,
                                                                            ) =>
                                                                                inventoryItem.id ===
                                                                                value,
                                                                        );
                                                                    const currentBalance =
                                                                        locationBalanceFor(
                                                                            value,
                                                                        );

                                                                    return {
                                                                        ...currentLine,
                                                                        inventory_item_id:
                                                                            value,
                                                                        inventory_batch_id:
                                                                            '',
                                                                        actual_quantity:
                                                                            currentBalance.toFixed(
                                                                                3,
                                                                            ),
                                                                        unit_cost:
                                                                            currentLine.unit_cost ||
                                                                            item?.default_purchase_price ||
                                                                            '',
                                                                    };
                                                                },
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
                                                <TableCell className="text-right align-top font-medium">
                                                    {systemQty.toFixed(3)}
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <Input
                                                        type="number"
                                                        step="any"
                                                        min="0"
                                                        value={
                                                            line.actual_quantity
                                                        }
                                                        onChange={(event) =>
                                                            updateLine(
                                                                index,
                                                                'actual_quantity',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        placeholder="Actual quantity"
                                                    />
                                                    <InputError
                                                        message={
                                                            form.errors[
                                                                `items.${index}.actual_quantity` as keyof typeof form.errors
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right align-top font-medium">
                                                    {variance.toFixed(3)}
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    {isLoss ? (
                                                        <>
                                                            <SearchableSelect
                                                                options={
                                                                    availableBatchOptions
                                                                }
                                                                value={
                                                                    line.inventory_batch_id
                                                                }
                                                                onValueChange={(
                                                                    value,
                                                                ) =>
                                                                    updateLine(
                                                                        index,
                                                                        'inventory_batch_id',
                                                                        value,
                                                                    )
                                                                }
                                                                placeholder="Select batch for loss"
                                                                emptyMessage="No matching batches."
                                                                allowClear
                                                            />
                                                            <InputError
                                                                message={
                                                                    form.errors[
                                                                        `items.${index}.inventory_batch_id` as keyof typeof form.errors
                                                                    ]
                                                                }
                                                            />
                                                        </>
                                                    ) : (
                                                        <div className="rounded-md border px-3 py-2 text-sm text-muted-foreground">
                                                            Only needed when
                                                            actual quantity is
                                                            lower than system
                                                            quantity.
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <Input
                                                        type="number"
                                                        step="any"
                                                        min="0"
                                                        value={line.unit_cost}
                                                        onChange={(event) =>
                                                            updateLine(
                                                                index,
                                                                'unit_cost',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            form.errors[
                                                                `items.${index}.unit_cost` as keyof typeof form.errors
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <Input
                                                        value={
                                                            line.batch_number
                                                        }
                                                        onChange={(event) =>
                                                            updateLine(
                                                                index,
                                                                'batch_number',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        placeholder="Optional for gains"
                                                    />
                                                    <InputError
                                                        message={
                                                            form.errors[
                                                                `items.${index}.batch_number` as keyof typeof form.errors
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <Input
                                                        type="date"
                                                        value={line.expiry_date}
                                                        onChange={(event) =>
                                                            updateLine(
                                                                index,
                                                                'expiry_date',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            form.errors[
                                                                `items.${index}.expiry_date` as keyof typeof form.errors
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <Textarea
                                                        rows={2}
                                                        value={line.notes}
                                                        onChange={(event) =>
                                                            updateLine(
                                                                index,
                                                                'notes',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        placeholder="Optional line note"
                                                    />
                                                    <InputError
                                                        message={
                                                            form.errors[
                                                                `items.${index}.notes` as keyof typeof form.errors
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right align-top">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            removeLine(index)
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>
                    </div>

                    <div className="flex gap-3">
                        <Button
                            type="submit"
                            disabled={
                                form.processing || form.data.items.length === 0
                            }
                        >
                            {form.processing ? (
                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <PlusCircle className="mr-2 h-4 w-4" />
                            )}
                            Create Reconciliation
                        </Button>
                        <Button variant="ghost" type="button" asChild>
                            <Link href="/reconciliations">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
