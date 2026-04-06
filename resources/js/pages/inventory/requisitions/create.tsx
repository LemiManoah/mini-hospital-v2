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
import { type InventoryRequisitionFormPageProps } from '@/types/inventory-requisition';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, PlusCircle, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';

type RequisitionLine = {
    inventory_item_id: string;
    requested_quantity: string;
    notes: string;
};

const emptyLine = (): RequisitionLine => ({
    inventory_item_id: '',
    requested_quantity: '',
    notes: '',
});

export default function InventoryRequisitionCreate({
    navigation,
    sourceInventoryLocations,
    destinationInventoryLocations,
    inventoryItems,
    priorityOptions,
}: InventoryRequisitionFormPageProps) {
    const isRequesterWorkspace = navigation.key !== 'inventory';
    const singleSourceLocation =
        sourceInventoryLocations.length === 1
            ? sourceInventoryLocations[0]
            : null;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        {
            title: navigation.requisitions_title,
            href: navigation.requisitions_href,
        },
        {
            title: navigation.requisition_create_title,
            href: `${navigation.requisitions_href}/create`,
        },
    ];

    const form = useForm({
        source_inventory_location_id: sourceInventoryLocations[0]?.id ?? '',
        destination_inventory_location_id: '',
        requisition_date: new Date().toISOString().split('T')[0],
        priority: priorityOptions[0]?.value ?? 'routine',
        notes: '',
        items: [emptyLine()],
    });

    const sourceLocationOptions = sourceInventoryLocations.map((location) => ({
        value: location.id,
        label: `${location.name} (${location.location_code})`,
    }));

    const destinationLocationOptions = destinationInventoryLocations.map(
        (location) => ({
        value: location.id,
        label: `${location.name} (${location.location_code})`,
        }),
    );

    const itemOptions = inventoryItems.map((item) => ({
        value: item.id,
        label: item.generic_name ?? item.name,
    }));

    const updateLine = (
        index: number,
        field: keyof RequisitionLine,
        value: string,
    ) => {
        const updated = [...form.data.items];
        updated[index] = { ...updated[index], [field]: value };
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
        form.post(navigation.requisitions_href);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={navigation.requisition_create_title} />

            <div className="m-4 max-w-7xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {navigation.requisition_create_title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {isRequesterWorkspace
                                ? 'Raise a stock request from this unit to the main store. The requisition stays in draft until you submit it for main store review.'
                                : 'Create an internal stock request within the active branch. The requisition number will be generated automatically when you save.'}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={navigation.requisitions_href}>Back</Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>
                                    {isRequesterWorkspace
                                        ? 'Issuing Store'
                                        : 'Source Location'}
                                </Label>
                                {isRequesterWorkspace && singleSourceLocation ? (
                                    <div className="rounded-md border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800">
                                        {singleSourceLocation.name} (
                                        {singleSourceLocation.location_code})
                                    </div>
                                ) : (
                                    <SearchableSelect
                                        options={sourceLocationOptions}
                                        value={
                                            form.data
                                                .source_inventory_location_id
                                        }
                                        onValueChange={(value) =>
                                            form.setData(
                                                'source_inventory_location_id',
                                                value,
                                            )
                                        }
                                        placeholder="Select source location"
                                        emptyMessage="No locations found."
                                    />
                                )}
                                <InputError
                                    message={
                                        form.errors.source_inventory_location_id
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>
                                    {isRequesterWorkspace
                                        ? 'Requesting Unit'
                                        : 'Destination Location'}
                                </Label>
                                <SearchableSelect
                                    options={destinationLocationOptions.filter(
                                        (location) =>
                                            location.value !==
                                            form.data
                                                .source_inventory_location_id,
                                    )}
                                    value={
                                        form.data
                                            .destination_inventory_location_id
                                    }
                                    onValueChange={(value) =>
                                        form.setData(
                                            'destination_inventory_location_id',
                                            value,
                                        )
                                    }
                                    placeholder="Select destination location"
                                    emptyMessage="No locations found."
                                />
                                <InputError
                                    message={
                                        form.errors.destination_inventory_location_id
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="requisition_date">
                                    Requisition Date
                                </Label>
                                <Input
                                    id="requisition_date"
                                    type="date"
                                    value={form.data.requisition_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'requisition_date',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={form.errors.requisition_date}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>Priority</Label>
                                <SearchableSelect
                                    options={priorityOptions}
                                    value={form.data.priority}
                                    onValueChange={(value) =>
                                        form.setData('priority', value)
                                    }
                                    placeholder="Select priority"
                                    emptyMessage="No priorities found."
                                />
                                <InputError message={form.errors.priority} />
                            </div>

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    rows={3}
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData(
                                            'notes',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Why is this stock being requested?"
                                />
                                <InputError message={form.errors.notes} />
                            </div>
                        </div>
                    </div>

                    <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-medium">
                                    Requested Items
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Add each stock item and the quantity needed
                                    {isRequesterWorkspace
                                        ? ' in this unit so the main store can review and issue it.'
                                        : ' at the destination location.'}
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
                            <Table className="min-w-[900px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-80">
                                            Item
                                        </TableHead>
                                        <TableHead className="w-40">
                                            Requested Qty
                                        </TableHead>
                                        <TableHead>Notes</TableHead>
                                        <TableHead className="w-16" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {form.data.items.map((line, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="align-top">
                                                <SearchableSelect
                                                    options={itemOptions}
                                                    value={line.inventory_item_id}
                                                    onValueChange={(value) =>
                                                        updateLine(
                                                            index,
                                                            'inventory_item_id',
                                                            value,
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
                                            <TableCell className="align-top">
                                                <Input
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    value={
                                                        line.requested_quantity
                                                    }
                                                    onChange={(event) =>
                                                        updateLine(
                                                            index,
                                                            'requested_quantity',
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="Quantity"
                                                />
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `items.${index}.requested_quantity` as keyof typeof form.errors
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
                                    ))}
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
                            {isRequesterWorkspace
                                ? 'Save Requisition Draft'
                                : 'Create Requisition'}
                        </Button>
                        <Button variant="ghost" type="button" asChild>
                            <Link href={navigation.requisitions_href}>
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
