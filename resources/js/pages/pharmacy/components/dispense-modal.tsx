import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type {
    PharmacyAvailableBatchBalance,
    PharmacyLocationOption,
    PharmacyPolicy,
    PharmacyQueuePrescription,
} from '@/types/pharmacy';
import { useForm } from '@inertiajs/react';
import { PlusCircle, Trash2 } from 'lucide-react';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    prescription: PharmacyQueuePrescription | null;
    dispensingLocations: PharmacyLocationOption[];
    availableBatchBalances: PharmacyAvailableBatchBalance[];
    pharmacyPolicy: PharmacyPolicy;
};

type DispenseModalForm = {
    inventory_location_id: string;
    dispensed_at: string;
    notes: string;
    items: Array<{
        prescription_item_id: string;
        dispensed_quantity: string;
        external_pharmacy: boolean;
        external_reason: string;
        notes: string;
        allocations: Array<{
            inventory_batch_id: string;
            quantity: string;
        }>;
    }>;
};

const badgeTone = (value: string | null | undefined): string => {
    switch (value) {
        case 'ready':
        case 'dispensed':
        case 'fully_dispensed':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'partial':
        case 'partially_dispensed':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'out_of_stock':
        case 'cancelled':
            return 'border-rose-200 bg-rose-50 text-rose-700';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700';
    }
};

export function DispenseModal({
    open,
    onOpenChange,
    prescription,
    dispensingLocations,
    availableBatchBalances,
    pharmacyPolicy,
}: Props) {
    if (!prescription) {
        return null;
    }

    const form = useForm<DispenseModalForm>({
        inventory_location_id: dispensingLocations[0]?.id ?? '',
        dispensed_at: new Date().toISOString().slice(0, 16),
        notes: '',
        items: prescription.items.map((item) => ({
            prescription_item_id: item.id,
            dispensed_quantity: item.quantity.toFixed(3),
            external_pharmacy: false,
            external_reason: '',
            notes: '',
            allocations: [],
        })),
    });

    const updateItem = <K extends keyof DispenseModalForm['items'][number]>(
        index: number,
        field: K,
        value: DispenseModalForm['items'][number][K],
    ) => {
        const items = [...form.data.items];
        items[index] = {
            ...items[index],
            [field]: value,
        };
        form.setData('items', items);
    };

    const setLocation = (locationId: string) => {
        form.setData({
            ...form.data,
            inventory_location_id: locationId,
            items: form.data.items.map((item) => ({
                ...item,
                allocations: [],
            })),
        });
    };

    const addAllocation = (lineIndex: number) => {
        const items = [...form.data.items];
        items[lineIndex] = {
            ...items[lineIndex],
            allocations: [
                ...items[lineIndex].allocations,
                { inventory_batch_id: '', quantity: '' },
            ],
        };
        form.setData('items', items);
    };

    const removeAllocation = (lineIndex: number, allocationIndex: number) => {
        const items = [...form.data.items];
        items[lineIndex] = {
            ...items[lineIndex],
            allocations: items[lineIndex].allocations.filter(
                (_, index) => index !== allocationIndex,
            ),
        };
        form.setData('items', items);
    };

    const updateAllocation = (
        lineIndex: number,
        allocationIndex: number,
        field: 'inventory_batch_id' | 'quantity',
        value: string,
    ) => {
        const items = [...form.data.items];
        const allocations = [...items[lineIndex].allocations];
        allocations[allocationIndex] = {
            ...allocations[allocationIndex],
            [field]: value,
        };
        items[lineIndex] = {
            ...items[lineIndex],
            allocations,
        };
        form.setData('items', items);
    };

    const batchOptionsFor = (inventoryItemId: string) =>
        availableBatchBalances
            .filter(
                (batch) =>
                    batch.inventory_location_id ===
                        form.data.inventory_location_id &&
                    batch.inventory_item_id === inventoryItemId,
            )
            .map((batch) => ({
                value: batch.inventory_batch_id,
                label: `${batch.batch_number ?? 'No batch'} | Qty ${batch.quantity.toFixed(3)}${batch.expiry_date ? ` | Exp ${batch.expiry_date}` : ''}`,
            }));

    const quantityForLocation = (inventoryItemId: string) =>
        availableBatchBalances
            .filter(
                (batch) =>
                    batch.inventory_location_id ===
                        form.data.inventory_location_id &&
                    batch.inventory_item_id === inventoryItemId,
            )
            .reduce((sum, batch) => sum + batch.quantity, 0);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Dispense Medication</DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="inventory_location_id">
                                Dispensing Location
                            </Label>
                            <Select
                                value={form.data.inventory_location_id}
                                onValueChange={setLocation}
                            >
                                <SelectTrigger id="inventory_location_id">
                                    <SelectValue placeholder="Select dispensing point" />
                                </SelectTrigger>
                                <SelectContent>
                                    {dispensingLocations.map((location) => (
                                        <SelectItem
                                            key={location.id}
                                            value={location.id}
                                        >
                                            {location.name} (
                                            {location.location_code})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={form.errors.inventory_location_id}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="dispensed_at">
                                Dispense Time
                            </Label>
                            <Input
                                id="dispensed_at"
                                type="datetime-local"
                                value={form.data.dispensed_at}
                                onChange={(event) =>
                                    form.setData(
                                        'dispensed_at',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.dispensed_at} />
                        </div>
                    </div>

                    <InputError message={form.errors.items} />

                    <div className="space-y-4">
                        {prescription.items.map((item, index) => {
                            const selectedQuantity =
                                form.data.items[index]?.dispensed_quantity ?? '';
                            const numericQuantity = Number(selectedQuantity || 0);
                            const selectedLocationAvailable = quantityForLocation(
                                item.inventory_item_id,
                            );
                            const allocations =
                                form.data.items[index]?.allocations ?? [];
                            const allocatedQuantity = allocations.reduce(
                                (sum, allocation) =>
                                    sum + Number(allocation.quantity || 0),
                                0,
                            );
                            const remainingAllocation = Math.max(
                                numericQuantity - allocatedQuantity,
                                0,
                            );
                            const batchOptions = batchOptionsFor(
                                item.inventory_item_id,
                            );

                            return (
                                <div
                                    key={item.id}
                                    className="space-y-4 border-b pb-5 last:border-b-0 last:pb-0"
                                >
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="space-y-1">
                                            <div className="font-medium">
                                                {item.item_name ??
                                                    item.generic_name ??
                                                    'Medication'}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {item.dosage} / {item.frequency}{' '}
                                                / {item.route}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                Prescribed:{' '}
                                                {item.quantity.toFixed(3)} /
                                                Available:{' '}
                                                {selectedLocationAvailable.toFixed(
                                                    3,
                                                )}
                                            </div>
                                            {item.instructions ? (
                                                <div className="text-sm text-muted-foreground">
                                                    {item.instructions}
                                                </div>
                                            ) : null}
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Badge
                                                variant="outline"
                                                className={badgeTone(
                                                    item.status,
                                                )}
                                            >
                                                {item.status_label ?? 'Pending'}
                                            </Badge>
                                            <Badge
                                                variant="outline"
                                                className={badgeTone(
                                                    item.stock_status,
                                                )}
                                            >
                                                {item.stock_status_label ??
                                                    'Unknown'}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div className="grid gap-4 lg:grid-cols-3">
                                        <div className="space-y-2">
                                            <Label>
                                                Quantity To Dispense
                                            </Label>
                                            {pharmacyPolicy.allow_partial_dispense ? (
                                                <Input
                                                    type="number"
                                                    step="0.001"
                                                    min="0"
                                                    value={selectedQuantity}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'dispensed_quantity',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                            ) : (
                                                <Select
                                                    value={selectedQuantity}
                                                    onValueChange={(value) =>
                                                        updateItem(
                                                            index,
                                                            'dispensed_quantity',
                                                            value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select quantity" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="0.000">
                                                            No local dispense
                                                        </SelectItem>
                                                        <SelectItem
                                                            value={item.quantity.toFixed(
                                                                3,
                                                            )}
                                                        >
                                                            Full quantity (
                                                            {item.quantity.toFixed(
                                                                3,
                                                            )}
                                                            )
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            )}
                                            <InputError
                                                message={
                                                    form.errors[
                                                        `items.${index}.dispensed_quantity`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>External Pharmacy</Label>
                                            <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <input
                                                    type="checkbox"
                                                    className="h-4 w-4"
                                                    checked={
                                                        form.data.items[index]
                                                            ?.external_pharmacy ??
                                                        false
                                                    }
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'external_pharmacy',
                                                            event.target.checked,
                                                        )
                                                    }
                                                />
                                                Mark any remainder as external
                                            </label>
                                            <InputError
                                                message={
                                                    form.errors[
                                                        `items.${index}.external_pharmacy`
                                                    ]
                                                }
                                            />
                                            <Textarea
                                                value={
                                                    form.data.items[index]
                                                        ?.external_reason ?? ''
                                                }
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'external_reason',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Reason for external sourcing, if applicable"
                                            />
                                            <InputError
                                                message={
                                                    form.errors[
                                                        `items.${index}.external_reason`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label>Line Note</Label>
                                            <Textarea
                                                value={
                                                    form.data.items[index]
                                                        ?.notes ?? ''
                                                }
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'notes',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Optional note"
                                            />
                                        </div>
                                    </div>

                                    {pharmacyPolicy.batch_tracking_enabled &&
                                    numericQuantity > 0 ? (
                                        <div className="space-y-3 rounded-lg bg-muted/30 p-3">
                                            {allocations.length > 0 ? (
                                                <>
                                                    {allocations.map(
                                                        (
                                                            allocation,
                                                            allocationIndex,
                                                        ) => (
                                                            <div
                                                                key={`${item.id}-${allocationIndex}`}
                                                                className="grid gap-3 md:grid-cols-[1.6fr_1fr_auto]"
                                                            >
                                                                <div className="grid gap-2">
                                                                    <Label>
                                                                        Batch
                                                                    </Label>
                                                                    <SearchableSelect
                                                                        options={
                                                                            batchOptions
                                                                        }
                                                                        value={
                                                                            allocation.inventory_batch_id
                                                                        }
                                                                        onValueChange={(
                                                                            value,
                                                                        ) =>
                                                                            updateAllocation(
                                                                                index,
                                                                                allocationIndex,
                                                                                'inventory_batch_id',
                                                                                value,
                                                                            )
                                                                        }
                                                                        placeholder="Select batch"
                                                                        emptyMessage="No matching batches."
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            form
                                                                                .errors[
                                                                                `items.${index}.allocations.${allocationIndex}.inventory_batch_id`
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label>
                                                                        Quantity
                                                                    </Label>
                                                                    <Input
                                                                        type="number"
                                                                        step="0.001"
                                                                        min="0"
                                                                        value={
                                                                            allocation.quantity
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            updateAllocation(
                                                                                index,
                                                                                allocationIndex,
                                                                                'quantity',
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
                                                                                `items.${index}.allocations.${allocationIndex}.quantity`
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="flex items-end">
                                                                    <Button
                                                                        type="button"
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        onClick={() =>
                                                                            removeAllocation(
                                                                                index,
                                                                                allocationIndex,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </div>
                                                            </div>
                                                        ),
                                                    )}
                                                    <div className="flex flex-col gap-3 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between">
                                                        <div className="text-sm text-muted-foreground">
                                                            Allocated:{' '}
                                                            {allocatedQuantity.toFixed(
                                                                3,
                                                            )}{' '}
                                                            /{' '}
                                                            {numericQuantity.toFixed(
                                                                3,
                                                            )}
                                                            {remainingAllocation >
                                                            0 ? (
                                                                <span>
                                                                    {' '}
                                                                    |
                                                                    Remaining{' '}
                                                                    {remainingAllocation.toFixed(
                                                                        3,
                                                                    )}
                                                                </span>
                                                            ) : (
                                                                <span>
                                                                    {' '}
                                                                    | Complete
                                                                </span>
                                                            )}
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                addAllocation(
                                                                    index,
                                                                )
                                                            }
                                                            disabled={
                                                                batchOptions.length ===
                                                                    0 ||
                                                                remainingAllocation <=
                                                                    0
                                                            }
                                                        >
                                                            <PlusCircle className="mr-2 h-4 w-4" />
                                                            Add Batch
                                                        </Button>
                                                    </div>
                                                </>
                                            ) : (
                                                <div className="flex items-center justify-between gap-3">
                                                    <p className="text-sm text-muted-foreground">
                                                        No batches selected yet.
                                                    </p>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            addAllocation(index)
                                                        }
                                                        disabled={
                                                            batchOptions.length ===
                                                            0
                                                        }
                                                    >
                                                        <PlusCircle className="mr-2 h-4 w-4" />
                                                        Add Batch
                                                    </Button>
                                                </div>
                                            )}

                                            <InputError
                                                message={
                                                    form.errors[
                                                        `items.${index}.allocations`
                                                    ]
                                                }
                                            />
                                        </div>
                                    ) : null}
                                </div>
                            );
                        })}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="notes">Pharmacy Note</Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                            placeholder="Optional overall note for this dispense"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        disabled={form.processing || dispensingLocations.length === 0}
                        onClick={() =>
                            form.post(
                                `/pharmacy/prescriptions/${prescription.id}/dispense`,
                                {
                                    preserveScroll: true,
                                    onSuccess: () => onOpenChange(false),
                                },
                            )
                        }
                    >
                        Confirm Dispense
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
