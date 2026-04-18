import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { type DispenseCreatePageProps } from '@/types/pharmacy';
import { Head, Link, useForm } from '@inertiajs/react';

type DispenseForm = {
    inventory_location_id: string;
    dispensed_at: string;
    notes: string;
    items: Array<{
        prescription_item_id: string;
        dispensed_quantity: string;
        external_pharmacy: boolean;
        external_reason: string;
        notes: string;
    }>;
};

export default function DispenseCreatePage({
    navigation,
    prescription,
    dispensingLocations,
    defaults,
    pharmacyPolicy,
}: DispenseCreatePageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        {
            title: navigation.queue_title ?? 'Pharmacy Queue',
            href: navigation.queue_href ?? '/pharmacy/queue',
        },
        {
            title: prescription.visit_number ?? 'Prescription',
            href: `/pharmacy/prescriptions/${prescription.id}`,
        },
        {
            title: 'Create Dispense Record',
            href: `/pharmacy/prescriptions/${prescription.id}/dispenses/create`,
        },
    ];

    const form = useForm<DispenseForm>({
        inventory_location_id: defaults.inventory_location_id ?? '',
        dispensed_at: defaults.dispensed_at,
        notes: '',
        items: prescription.items.map((item) => ({
            prescription_item_id: item.id,
            dispensed_quantity: item.quantity.toFixed(3),
            external_pharmacy: false,
            external_reason: '',
            notes: '',
        })),
    });

    const updateItem = <K extends keyof DispenseForm['items'][number]>(
        index: number,
        field: K,
        value: DispenseForm['items'][number][K],
    ) => {
        const items = [...form.data.items];
        items[index] = {
            ...items[index],
            [field]: value,
        };
        form.setData('items', items);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Dispense Record" />

            <div className="m-4 flex max-w-6xl flex-col gap-6">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold">
                            Create Dispense Record
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Capture the dispensing document first, then review
                            and post stock movements from the saved record.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={`/pharmacy/prescriptions/${prescription.id}`}
                            >
                                Back To Prescription
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Dispensing Policy</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-muted-foreground">
                        <p>
                            Batch tracking:{' '}
                            {pharmacyPolicy.batch_tracking_enabled
                                ? 'manual batch allocation will be required before stock can be posted.'
                                : 'stock will be auto-allocated from available pharmacy batches when the record is posted.'}
                        </p>
                        <p>
                            Partial dispensing:{' '}
                            {pharmacyPolicy.allow_partial_dispense
                                ? 'allowed for this tenant.'
                                : 'disabled for this tenant, so each line must be either zero or the full prescribed quantity.'}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Prescription Summary</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm md:grid-cols-2">
                        <div>
                            <div className="font-medium">
                                {prescription.patient?.full_name ??
                                    'Unknown patient'}
                            </div>
                            <div className="text-muted-foreground">
                                Patient No:{' '}
                                {prescription.patient?.patient_number ?? '-'}
                            </div>
                            <div className="text-muted-foreground">
                                Visit: {prescription.visit_number ?? '-'}
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Diagnosis:{' '}
                                {prescription.primary_diagnosis ?? '-'}
                            </div>
                            <div className="text-muted-foreground">
                                Notes: {prescription.pharmacy_notes ?? '-'}
                            </div>
                            <div className="text-muted-foreground">
                                Status: {prescription.status_label ?? '-'}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(
                            `/pharmacy/prescriptions/${prescription.id}/dispenses`,
                        );
                    }}
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Dispense Header</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="inventory_location_id">
                                    Dispensing Location
                                </Label>
                                <Select
                                    value={form.data.inventory_location_id}
                                    onValueChange={(value) =>
                                        form.setData(
                                            'inventory_location_id',
                                            value,
                                        )
                                    }
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
                                {form.errors.inventory_location_id ? (
                                    <p className="text-sm text-destructive">
                                        {form.errors.inventory_location_id}
                                    </p>
                                ) : null}
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
                                {form.errors.dispensed_at ? (
                                    <p className="text-sm text-destructive">
                                        {form.errors.dispensed_at}
                                    </p>
                                ) : null}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="notes">Pharmacy Notes</Label>
                                <Textarea
                                    id="notes"
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData(
                                            'notes',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Capture counselling, handover, or preparation notes..."
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Dispense Lines</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {prescription.items.map((item, index) => (
                                <div
                                    key={item.id}
                                    className="space-y-4 rounded-lg border p-4"
                                >
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="space-y-1">
                                            <div className="font-medium">
                                                {item.item_name ??
                                                    item.generic_name ??
                                                    'Medication'}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {item.dosage} • {item.frequency}{' '}
                                                • {item.route}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                Prescribed:{' '}
                                                {item.quantity.toFixed(3)} •
                                                Available:{' '}
                                                {item.available_quantity?.toFixed(
                                                    3,
                                                ) ?? '0.000'}
                                            </div>
                                        </div>
                                        <Badge variant="outline">
                                            {item.status_label ?? 'Pending'}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label
                                                htmlFor={`dispensed_quantity_${item.id}`}
                                            >
                                                Dispensed Quantity
                                            </Label>
                                            {pharmacyPolicy.allow_partial_dispense ? (
                                                <Input
                                                    id={`dispensed_quantity_${item.id}`}
                                                    type="number"
                                                    step="0.001"
                                                    min="0"
                                                    value={
                                                        form.data.items[index]
                                                            .dispensed_quantity
                                                    }
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
                                                    value={
                                                        form.data.items[index]
                                                            .dispensed_quantity
                                                    }
                                                    onValueChange={(value) =>
                                                        updateItem(
                                                            index,
                                                            'dispensed_quantity',
                                                            value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger
                                                        id={`dispensed_quantity_${item.id}`}
                                                    >
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
                                            {!pharmacyPolicy.allow_partial_dispense ? (
                                                <p className="text-xs text-muted-foreground">
                                                    This tenant does not allow
                                                    partial local dispensing.
                                                </p>
                                            ) : null}
                                            {form.errors[
                                                `items.${index}.dispensed_quantity`
                                            ] ? (
                                                <p className="text-sm text-destructive">
                                                    {
                                                        form.errors[
                                                            `items.${index}.dispensed_quantity`
                                                        ]
                                                    }
                                                </p>
                                            ) : null}
                                        </div>

                                        <div className="space-y-2">
                                            <Label
                                                htmlFor={`line_notes_${item.id}`}
                                            >
                                                Line Notes
                                            </Label>
                                            <Textarea
                                                id={`line_notes_${item.id}`}
                                                value={
                                                    form.data.items[index].notes
                                                }
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'notes',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Optional handover or review note"
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-3 rounded-md border border-dashed p-3">
                                        <div className="flex items-center gap-3">
                                            <Checkbox
                                                id={`external_${item.id}`}
                                                checked={
                                                    form.data.items[index]
                                                        .external_pharmacy
                                                }
                                                onCheckedChange={(checked) =>
                                                    updateItem(
                                                        index,
                                                        'external_pharmacy',
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <Label
                                                htmlFor={`external_${item.id}`}
                                            >
                                                Mark remainder for external
                                                pharmacy fulfilment
                                            </Label>
                                        </div>
                                        {form.errors[
                                            `items.${index}.external_pharmacy`
                                        ] ? (
                                            <p className="text-sm text-destructive">
                                                {
                                                    form.errors[
                                                        `items.${index}.external_pharmacy`
                                                    ]
                                                }
                                            </p>
                                        ) : null}
                                        <Textarea
                                            value={
                                                form.data.items[index]
                                                    .external_reason
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
                                        {form.errors[
                                            `items.${index}.external_reason`
                                        ] ? (
                                            <p className="text-sm text-destructive">
                                                {
                                                    form.errors[
                                                        `items.${index}.external_reason`
                                                    ]
                                                }
                                            </p>
                                        ) : null}
                                    </div>
                                </div>
                            ))}

                            {form.errors.items ? (
                                <p className="text-sm text-destructive">
                                    {form.errors.items}
                                </p>
                            ) : null}
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={`/pharmacy/prescriptions/${prescription.id}`}
                            >
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Save Dispense Record
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
