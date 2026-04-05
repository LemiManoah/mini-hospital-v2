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
import { type StockAdjustmentFormPageProps } from '@/types/stock-adjustment';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, LoaderCircle, PlusCircle, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Stock Adjustments', href: '/stock-adjustments' },
    { title: 'Create', href: '/stock-adjustments/create' },
];

type AdjustmentLine = {
    inventory_item_id: string;
    inventory_batch_id: string;
    quantity_delta: string;
    unit_cost: string;
    batch_number: string;
    expiry_date: string;
    notes: string;
};

const emptyLine = (): AdjustmentLine => ({
    inventory_item_id: '',
    inventory_batch_id: '',
    quantity_delta: '',
    unit_cost: '',
    batch_number: '',
    expiry_date: '',
    notes: '',
});

export default function StockAdjustmentCreate({
    inventoryLocations,
    inventoryItems,
    locationBalances,
    batchBalances,
}: StockAdjustmentFormPageProps) {
    const form = useForm({
        inventory_location_id: '',
        adjustment_date: new Date().toISOString().split('T')[0],
        reason: '',
        notes: '',
        items: [emptyLine()],
    });

    const locationOptions = inventoryLocations.map((location) => ({
        value: location.id,
        label: `${location.name} (${location.location_code})`,
    }));

    const itemOptions = inventoryItems.map((item) => ({
        value: item.id,
        label: item.generic_name ?? item.name,
    }));

    const updateLine = (
        index: number,
        field: keyof AdjustmentLine,
        value: string,
    ) => {
        const updated = [...form.data.items];
        updated[index] = { ...updated[index], [field]: value };
        form.setData('items', updated);
    };

    const replaceLine = (
        index: number,
        updater: (line: AdjustmentLine) => AdjustmentLine,
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

    const locationBalanceFor = (inventoryItemId: string): number => {
        const balance = locationBalances.find(
            (entry) =>
                entry.inventory_location_id === form.data.inventory_location_id &&
                entry.inventory_item_id === inventoryItemId,
        );

        return balance?.quantity ?? 0;
    };

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

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/stock-adjustments', {
            onSuccess: () =>
                toast.success('Stock adjustment created successfully.'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Stock Adjustment" />

            <div className="m-4 max-w-7xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Stock Adjustment
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Record gains, losses, corrections, or write-offs for
                            a single inventory location.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            The adjustment number will be generated
                            automatically when you save.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/stock-adjustments">Back</Link>
                    </Button>
                </div>

                <Alert>
                    <AlertTriangle className="h-4 w-4" />
                    <AlertTitle>How to use this form</AlertTitle>
                    <AlertDescription>
                        Use positive quantities for stock gains and negative
                        quantities for stock losses. Losses must reference an
                        existing batch in the selected location.
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
                                <Label htmlFor="adjustment_date">
                                    Adjustment Date
                                </Label>
                                <Input
                                    id="adjustment_date"
                                    type="date"
                                    value={form.data.adjustment_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'adjustment_date',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={form.errors.adjustment_date}
                                />
                            </div>

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="reason">Reason</Label>
                                <Input
                                    id="reason"
                                    value={form.data.reason}
                                    onChange={(event) =>
                                        form.setData('reason', event.target.value)
                                    }
                                    placeholder="Damage, stock count correction, expired stock, opening balance..."
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
                                        form.setData('notes', event.target.value)
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
                                    Adjustment Lines
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Each line updates one item in the selected
                                    location.
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
                            <Table className="min-w-[1200px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-56">Item</TableHead>
                                        <TableHead className="w-28 text-right">
                                            Current Qty
                                        </TableHead>
                                        <TableHead className="w-28">
                                            Qty Delta
                                        </TableHead>
                                        <TableHead className="w-48">
                                            Existing Batch
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
                                        const currentQty = locationBalanceFor(
                                            line.inventory_item_id,
                                        );
                                        const isLoss =
                                            Number(line.quantity_delta || 0) < 0;
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
                                                        onValueChange={(value) =>
                                                            replaceLine(
                                                                index,
                                                                (currentLine) => {
                                                                    const item =
                                                                        inventoryItems.find(
                                                                            (
                                                                                inventoryItem,
                                                                            ) =>
                                                                                inventoryItem.id ===
                                                                                value,
                                                                        );

                                                                    return {
                                                                        ...currentLine,
                                                                        inventory_item_id:
                                                                            value,
                                                                        inventory_batch_id:
                                                                            '',
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
                                                <TableCell className="align-top text-right font-medium">
                                                    {currentQty.toFixed(3)}
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <Input
                                                        type="number"
                                                        step="any"
                                                        value={
                                                            line.quantity_delta
                                                        }
                                                        onChange={(event) =>
                                                            updateLine(
                                                                index,
                                                                'quantity_delta',
                                                                event.target.value,
                                                            )
                                                        }
                                                        placeholder="-2 or 5"
                                                    />
                                                    <InputError
                                                        message={
                                                            form.errors[
                                                                `items.${index}.quantity_delta` as keyof typeof form.errors
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <SearchableSelect
                                                        options={[
                                                            {
                                                                value: '',
                                                                label: isLoss
                                                                    ? 'Select batch for loss'
                                                                    : 'No batch selected',
                                                            },
                                                            ...availableBatchOptions,
                                                        ]}
                                                        value={
                                                            line.inventory_batch_id
                                                        }
                                                        onValueChange={(value) =>
                                                            updateLine(
                                                                index,
                                                                'inventory_batch_id',
                                                                value,
                                                            )
                                                        }
                                                        placeholder="Select batch"
                                                        emptyMessage="No matching batches."
                                                    />
                                                    <InputError
                                                        message={
                                                            form.errors[
                                                                `items.${index}.inventory_batch_id` as keyof typeof form.errors
                                                            ]
                                                        }
                                                    />
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
                                                                event.target.value,
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
                                                        value={line.batch_number}
                                                        onChange={(event) =>
                                                            updateLine(
                                                                index,
                                                                'batch_number',
                                                                event.target.value,
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
                                                                event.target.value,
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
                                                                event.target.value,
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
                                                <TableCell className="align-top text-right">
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
                            disabled={form.processing || form.data.items.length === 0}
                        >
                            {form.processing ? (
                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <PlusCircle className="mr-2 h-4 w-4" />
                            )}
                            Create Adjustment
                        </Button>
                        <Button variant="ghost" type="button" asChild>
                            <Link href="/stock-adjustments">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
