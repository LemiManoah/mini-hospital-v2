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
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { type InventoryReconciliationFormPageProps } from '@/types/inventory-reconciliation';
import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronDown, LoaderCircle, PlusCircle, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';

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

    const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());

    const toggleExpand = (index: number) => {
        setExpandedRows((prev) => {
            const next = new Set(prev);
            if (next.has(index)) {
                next.delete(index);
            } else {
                next.add(index);
            }
            return next;
        });
    };

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
            setExpandedRows(new Set());
            return;
        }

        form.setData(
            'items',
            form.data.items.filter((_, itemIndex) => itemIndex !== index),
        );
        setExpandedRows((prev) => {
            const next = new Set<number>();
            prev.forEach((i) => {
                if (i < index) next.add(i);
                if (i > index) next.add(i - 1);
            });
            return next;
        });
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
                    <h1 className="text-2xl font-semibold">
                        New Reconciliation
                    </h1>
                    <Button variant="outline" asChild>
                        <Link href="/reconciliations">Back</Link>
                    </Button>
                </div>

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
                                    Date
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
                                    placeholder="Cycle count, damage, expiry, shelf verification..."
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
                            <h2 className="text-lg font-medium">Lines</h2>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addLine}
                            >
                                <PlusCircle className="mr-2 h-4 w-4" />
                                Add Line
                            </Button>
                        </div>

                        <InputError message={form.errors.items} />

                        <div className="overflow-x-auto">
                            <Table className="min-w-[860px]">
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
                                            Batch
                                        </TableHead>
                                        <TableHead className="w-28">
                                            Unit Cost
                                        </TableHead>
                                        <TableHead className="w-10" />
                                        <TableHead className="w-10" />
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
                                        const isGain = variance > 0;
                                        const availableBatchOptions =
                                            batchOptionsFor(
                                                line.inventory_item_id,
                                            );
                                        const isExpanded =
                                            isGain || expandedRows.has(index);

                                        return (
                                            <>
                                                <TableRow key={`row-${index}`}>
                                                    <TableCell className="align-top">
                                                        <SearchableSelect
                                                            options={
                                                                itemOptions
                                                            }
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
                                                            placeholder="0.000"
                                                        />
                                                        <InputError
                                                            message={
                                                                form.errors[
                                                                    `items.${index}.actual_quantity` as keyof typeof form.errors
                                                                ]
                                                            }
                                                        />
                                                    </TableCell>

                                                    <TableCell
                                                        className={cn(
                                                            'text-right align-top font-medium',
                                                            isLoss &&
                                                                'text-red-600 dark:text-red-400',
                                                            isGain &&
                                                                'text-green-600 dark:text-green-400',
                                                        )}
                                                    >
                                                        {variance !== 0
                                                            ? (variance > 0
                                                                  ? '+'
                                                                  : '') +
                                                              variance.toFixed(
                                                                  3,
                                                              )
                                                            : '—'}
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
                                                                    placeholder="Select batch"
                                                                    emptyMessage="No batches."
                                                                    allowClear
                                                                />
                                                                <InputError
                                                                    message={
                                                                        form
                                                                            .errors[
                                                                            `items.${index}.inventory_batch_id` as keyof typeof form.errors
                                                                        ]
                                                                    }
                                                                />
                                                            </>
                                                        ) : (
                                                            <span className="text-sm text-muted-foreground">
                                                                —
                                                            </span>
                                                        )}
                                                    </TableCell>

                                                    <TableCell className="align-top">
                                                        <Input
                                                            type="number"
                                                            step="any"
                                                            min="0"
                                                            value={
                                                                line.unit_cost
                                                            }
                                                            onChange={(event) =>
                                                                updateLine(
                                                                    index,
                                                                    'unit_cost',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            placeholder="0.00"
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
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                toggleExpand(
                                                                    index,
                                                                )
                                                            }
                                                            title={
                                                                isExpanded
                                                                    ? 'Collapse'
                                                                    : 'Expand'
                                                            }
                                                        >
                                                            <ChevronDown
                                                                className={cn(
                                                                    'h-4 w-4 transition-transform',
                                                                    isExpanded &&
                                                                        'rotate-180',
                                                                )}
                                                            />
                                                        </Button>
                                                    </TableCell>

                                                    <TableCell className="align-top">
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                removeLine(
                                                                    index,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>

                                                {isExpanded ? (
                                                    <TableRow
                                                        key={`expand-${index}`}
                                                        className="bg-zinc-50 dark:bg-zinc-800/50"
                                                    >
                                                        <TableCell
                                                            colSpan={8}
                                                            className="px-4 py-3"
                                                        >
                                                            <div className="grid grid-cols-3 gap-4">
                                                                <div className="grid gap-1.5">
                                                                    <Label className="text-xs">
                                                                        New
                                                                        Batch #
                                                                    </Label>
                                                                    <Input
                                                                        value={
                                                                            line.batch_number
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            updateLine(
                                                                                index,
                                                                                'batch_number',
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                        placeholder="Optional for gains"
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            form
                                                                                .errors[
                                                                                `items.${index}.batch_number` as keyof typeof form.errors
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="grid gap-1.5">
                                                                    <Label className="text-xs">
                                                                        Expiry
                                                                        Date
                                                                    </Label>
                                                                    <Input
                                                                        type="date"
                                                                        value={
                                                                            line.expiry_date
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            updateLine(
                                                                                index,
                                                                                'expiry_date',
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            form
                                                                                .errors[
                                                                                `items.${index}.expiry_date` as keyof typeof form.errors
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="grid gap-1.5">
                                                                    <Label className="text-xs">
                                                                        Notes
                                                                    </Label>
                                                                    <Textarea
                                                                        rows={2}
                                                                        value={
                                                                            line.notes
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            updateLine(
                                                                                index,
                                                                                'notes',
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                        placeholder="Optional note"
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            form
                                                                                .errors[
                                                                                `items.${index}.notes` as keyof typeof form.errors
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ) : null}
                                            </>
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
                            ) : null}
                            Save Reconciliation
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
