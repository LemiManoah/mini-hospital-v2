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
import { type StockCountFormPageProps } from '@/types/stock-count';
import { Head, Link, useForm } from '@inertiajs/react';
import { ClipboardCheck, LoaderCircle, PlusCircle, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Stock Counts', href: '/stock-counts' },
    { title: 'Create', href: '/stock-counts/create' },
];

type CountLine = {
    inventory_item_id: string;
    counted_quantity: string;
    notes: string;
};

const emptyLine = (): CountLine => ({
    inventory_item_id: '',
    counted_quantity: '',
    notes: '',
});

export default function StockCountCreate({
    inventoryLocations,
    inventoryItems,
    locationBalances,
}: StockCountFormPageProps) {
    const form = useForm({
        inventory_location_id: '',
        count_date: new Date().toISOString().split('T')[0],
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

    const expectedQuantityFor = (inventoryItemId: string): number => {
        const balance = locationBalances.find(
            (entry) =>
                entry.inventory_location_id === form.data.inventory_location_id &&
                entry.inventory_item_id === inventoryItemId,
        );

        return balance?.quantity ?? 0;
    };

    const updateLine = (
        index: number,
        field: keyof CountLine,
        value: string,
    ) => {
        const updated = [...form.data.items];
        updated[index] = { ...updated[index], [field]: value };
        form.setData('items', updated);
    };

    const replaceLine = (
        index: number,
        updater: (line: CountLine) => CountLine,
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
        form.post('/stock-counts', {
            onSuccess: () => toast.success('Stock count created successfully.'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Stock Count" />

            <div className="m-4 max-w-7xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Stock Count
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Record the physical quantity found in one inventory
                            location. Variances are calculated automatically
                            from current ledger balances.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            The count number will be generated automatically
                            when you save.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/stock-counts">Back</Link>
                    </Button>
                </div>

                <Alert>
                    <ClipboardCheck className="h-4 w-4" />
                    <AlertTitle>Count posting rule</AlertTitle>
                    <AlertDescription>
                        Posting compares the recorded expected balance to the
                        live ledger balance. If stock moved after the count was
                        created, the system will stop you and ask for a fresh
                        count.
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
                                <Label htmlFor="count_date">Count Date</Label>
                                <Input
                                    id="count_date"
                                    type="date"
                                    value={form.data.count_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'count_date',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={form.errors.count_date}
                                />
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
                                    Count Lines
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Add each item you physically counted in the
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
                            <Table className="min-w-[1000px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-64">Item</TableHead>
                                        <TableHead className="w-32 text-right">
                                            Expected Qty
                                        </TableHead>
                                        <TableHead className="w-32">
                                            Counted Qty
                                        </TableHead>
                                        <TableHead className="w-32 text-right">
                                            Variance
                                        </TableHead>
                                        <TableHead>Notes</TableHead>
                                        <TableHead className="w-16" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {form.data.items.map((line, index) => {
                                        const expectedQty =
                                            expectedQuantityFor(
                                                line.inventory_item_id,
                                            );
                                        const countedQty = Number(
                                            line.counted_quantity || 0,
                                        );
                                        const variance =
                                            countedQty - expectedQty;

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
                                                                (
                                                                    currentLine,
                                                                ) => ({
                                                                    ...currentLine,
                                                                    inventory_item_id:
                                                                        value,
                                                                    counted_quantity:
                                                                        expectedQuantityFor(
                                                                            value,
                                                                        ).toFixed(
                                                                            3,
                                                                        ),
                                                                }),
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
                                                    {expectedQty.toFixed(3)}
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <Input
                                                        type="number"
                                                        step="any"
                                                        min="0"
                                                        value={
                                                            line.counted_quantity
                                                        }
                                                        onChange={(event) =>
                                                            updateLine(
                                                                index,
                                                                'counted_quantity',
                                                                event.target.value,
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            form.errors[
                                                                `items.${index}.counted_quantity` as keyof typeof form.errors
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="align-top text-right font-medium">
                                                    {variance.toFixed(3)}
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
                            Create Count
                        </Button>
                        <Button variant="ghost" type="button" asChild>
                            <Link href="/stock-counts">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
