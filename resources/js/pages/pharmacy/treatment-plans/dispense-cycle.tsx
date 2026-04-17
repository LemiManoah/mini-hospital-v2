import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PharmacyTreatmentPlanCycleDispensePageProps } from '@/types/pharmacy';
import { Head, Link, useForm } from '@inertiajs/react';
import { PlusCircle, Trash2 } from 'lucide-react';

type CycleDispenseForm = {
    inventory_location_id: string;
    dispensed_at: string;
    notes: string;
    items: Array<{
        pharmacy_treatment_plan_item_id: string;
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

export default function PharmacyTreatmentPlanCycleDispensePage({
    navigation,
    treatmentPlan,
    dispensingLocations,
    availableBatchBalances,
    pharmacyPolicy,
    defaults,
}: PharmacyTreatmentPlanCycleDispensePageProps) {
    const form = useForm<CycleDispenseForm>({
        inventory_location_id: defaults.inventory_location_id ?? '',
        dispensed_at: defaults.dispensed_at,
        notes: '',
        items: treatmentPlan.items.map((item) => ({
            pharmacy_treatment_plan_item_id: item.id,
            dispensed_quantity: item.quantity_per_cycle.toFixed(3),
            external_pharmacy: false,
            external_reason: '',
            notes: '',
            allocations: [],
        })),
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        { title: 'Treatment Plans', href: '/pharmacy/treatment-plans' },
        {
            title: treatmentPlan.visit_number ?? 'Treatment Plan',
            href: `/pharmacy/treatment-plans/${treatmentPlan.id}`,
        },
        {
            title: `Cycle ${treatmentPlan.cycle.cycle_number}`,
            href: `/pharmacy/treatment-plans/${treatmentPlan.id}/cycles/${treatmentPlan.cycle.id}/dispense`,
        },
    ];

    const updateItem = <K extends keyof CycleDispenseForm['items'][number]>(
        index: number,
        field: K,
        value: CycleDispenseForm['items'][number][K],
    ) => {
        const items = [...form.data.items];
        items[index] = {
            ...items[index],
            [field]: value,
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

    const batchOptionsFor = (inventoryItemId: string) =>
        availableBatchBalances
            .filter(
                (batch) =>
                    batch.inventory_location_id === form.data.inventory_location_id &&
                    batch.inventory_item_id === inventoryItemId,
            )
            .map((batch) => ({
                value: batch.inventory_batch_id,
                label: `${batch.batch_number ?? 'No batch'} | Qty ${batch.quantity.toFixed(3)}${batch.expiry_date ? ` | Exp ${batch.expiry_date}` : ''}`,
            }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Dispense Cycle ${treatmentPlan.cycle.cycle_number}`} />

            <div className="m-4 flex max-w-6xl flex-col gap-6">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold">
                        Dispense Cycle {treatmentPlan.cycle.cycle_number}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Patient: {treatmentPlan.patient?.full_name ?? '-'} / Due:{' '}
                        {treatmentPlan.cycle.scheduled_for ?? '-'}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Cycle Details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="inventory_location_id">
                                Dispensing Location
                            </Label>
                            <Select
                                value={form.data.inventory_location_id}
                                onValueChange={(value) =>
                                    form.setData('inventory_location_id', value)
                                }
                            >
                                <SelectTrigger id="inventory_location_id">
                                    <SelectValue placeholder="Select dispensing point" />
                                </SelectTrigger>
                                <SelectContent>
                                    {dispensingLocations.map((location) => (
                                        <SelectItem key={location.id} value={location.id}>
                                            {location.name} ({location.location_code})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.inventory_location_id} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="dispensed_at">Dispense Time</Label>
                            <Input
                                id="dispensed_at"
                                type="datetime-local"
                                value={form.data.dispensed_at}
                                onChange={(event) =>
                                    form.setData('dispensed_at', event.target.value)
                                }
                            />
                            <InputError message={form.errors.dispensed_at} />
                        </div>
                    </CardContent>
                </Card>

                <InputError message={form.errors.items} />

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(
                            `/pharmacy/treatment-plans/${treatmentPlan.id}/cycles/${treatmentPlan.cycle.id}/dispense`,
                        );
                    }}
                >
                    {treatmentPlan.items.map((item, index) => {
                        const allocations = form.data.items[index]?.allocations ?? [];
                        const selectedQuantity = form.data.items[index]?.dispensed_quantity ?? '';
                        const numericQuantity = Number(selectedQuantity || 0);
                        const allocatedQuantity = allocations.reduce(
                            (sum, allocation) => sum + Number(allocation.quantity || 0),
                            0,
                        );
                        const remainingAllocation = Math.max(
                            numericQuantity - allocatedQuantity,
                            0,
                        );
                        const batchOptions = batchOptionsFor(item.inventory_item_id);

                        return (
                            <Card key={item.id}>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        {item.generic_name ?? item.item_name ?? 'Medication'}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="text-sm text-muted-foreground">
                                        Scheduled quantity for this cycle:{' '}
                                        {item.quantity_per_cycle.toFixed(3)}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {[
                                            item.dosage,
                                            item.frequency,
                                            item.route,
                                        ]
                                            .filter(Boolean)
                                            .join(' / ')}
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-3">
                                        <div className="space-y-2">
                                            <Label>Local Quantity</Label>
                                            <Input
                                                type="number"
                                                step="0.001"
                                                min="0"
                                                max={item.quantity_per_cycle.toFixed(3)}
                                                value={selectedQuantity}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'dispensed_quantity',
                                                        event.target.value,
                                                    )
                                                }
                                            />
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
                                                            ?.external_pharmacy ?? false
                                                    }
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'external_pharmacy',
                                                            event.target.checked,
                                                        )
                                                    }
                                                />
                                                Send the remainder externally
                                            </label>
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
                                                placeholder="Reason if the remainder goes externally"
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
                                                value={form.data.items[index]?.notes ?? ''}
                                                onChange={(event) =>
                                                    updateItem(index, 'notes', event.target.value)
                                                }
                                                placeholder="Optional line note"
                                            />
                                        </div>
                                    </div>

                                    {pharmacyPolicy.batch_tracking_enabled &&
                                    numericQuantity > 0 ? (
                                        <div className="space-y-3 rounded-lg bg-muted/30 p-3">
                                            {allocations.length > 0 ? (
                                                <>
                                                    {allocations.map((allocation, allocationIndex) => (
                                                        <div
                                                            key={`${item.id}-${allocationIndex}`}
                                                            className="grid gap-3 md:grid-cols-[1.6fr_1fr_auto]"
                                                        >
                                                            <div className="grid gap-2">
                                                                <Label>Batch</Label>
                                                                <SearchableSelect
                                                                    options={batchOptions}
                                                                    value={
                                                                        allocation.inventory_batch_id
                                                                    }
                                                                    onValueChange={(value) =>
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
                                                            </div>
                                                            <div className="grid gap-2">
                                                                <Label>Quantity</Label>
                                                                <Input
                                                                    type="number"
                                                                    step="0.001"
                                                                    min="0"
                                                                    value={allocation.quantity}
                                                                    onChange={(event) =>
                                                                        updateAllocation(
                                                                            index,
                                                                            allocationIndex,
                                                                            'quantity',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                />
                                                            </div>
                                                            <div className="flex items-end">
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
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
                                                    ))}
                                                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                                                        <span>
                                                            Allocated:{' '}
                                                            {allocatedQuantity.toFixed(3)} /{' '}
                                                            {numericQuantity.toFixed(3)}
                                                        </span>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => addAllocation(index)}
                                                            disabled={
                                                                batchOptions.length === 0 ||
                                                                remainingAllocation <= 0
                                                            }
                                                        >
                                                            <PlusCircle className="mr-2 h-4 w-4" />
                                                            Add Batch
                                                        </Button>
                                                    </div>
                                                </>
                                            ) : (
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm text-muted-foreground">
                                                        No batches selected yet.
                                                    </span>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => addAllocation(index)}
                                                        disabled={batchOptions.length === 0}
                                                    >
                                                        <PlusCircle className="mr-2 h-4 w-4" />
                                                        Add Batch
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    ) : null}
                                </CardContent>
                            </Card>
                        );
                    })}

                    <Card>
                        <CardHeader>
                            <CardTitle>Cycle Note</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea
                                value={form.data.notes}
                                onChange={(event) =>
                                    form.setData('notes', event.target.value)
                                }
                                placeholder="Optional note for this cycle"
                            />
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href={`/pharmacy/treatment-plans/${treatmentPlan.id}`}>
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Dispense Cycle
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
