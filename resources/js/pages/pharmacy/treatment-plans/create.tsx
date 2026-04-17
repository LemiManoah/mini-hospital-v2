import InputError from '@/components/input-error';
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
import { type PharmacyTreatmentPlanCreatePageProps } from '@/types/pharmacy';
import { Head, Link, useForm } from '@inertiajs/react';

type FormShape = {
    start_date: string;
    frequency_unit: string;
    frequency_interval: string;
    total_authorized_cycles: string;
    notes: string;
    items: Array<{
        prescription_item_id: string;
        quantity_per_cycle: string;
        notes: string;
    }>;
};

export default function PharmacyTreatmentPlanCreatePage({
    navigation,
    prescription,
}: PharmacyTreatmentPlanCreatePageProps) {
    const form = useForm<FormShape>({
        start_date: new Date().toISOString().slice(0, 10),
        frequency_unit: 'weekly',
        frequency_interval: '1',
        total_authorized_cycles: '1',
        notes: '',
        items: prescription.items.map((item) => ({
            prescription_item_id: item.id,
            quantity_per_cycle: item.remaining_quantity.toFixed(3),
            notes: '',
        })),
    });

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
            title: 'Create Treatment Plan',
            href: `/pharmacy/prescriptions/${prescription.id}/treatment-plans/create`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Treatment Plan" />

            <div className="m-4 flex max-w-5xl flex-col gap-6">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold">Create Treatment Plan</h1>
                    <p className="text-sm text-muted-foreground">
                        Set up scheduled dispensing for long-term or staged
                        treatment from this prescription.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Prescription</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2 text-sm md:grid-cols-2">
                        <div>
                            Patient: {prescription.patient?.full_name ?? '-'}
                        </div>
                        <div>
                            Patient No: {prescription.patient?.patient_number ?? '-'}
                        </div>
                        <div>Visit: {prescription.visit_number ?? '-'}</div>
                        <div>
                            Prescribed:{' '}
                            {prescription.prescription_date
                                ? new Date(
                                      prescription.prescription_date,
                                  ).toLocaleString()
                                : '-'}
                        </div>
                    </CardContent>
                </Card>

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(
                            `/pharmacy/prescriptions/${prescription.id}/treatment-plans`,
                        );
                    }}
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>Schedule</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={form.data.start_date}
                                    onChange={(event) =>
                                        form.setData('start_date', event.target.value)
                                    }
                                />
                                <InputError message={form.errors.start_date} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="frequency_unit">Frequency Unit</Label>
                                <Select
                                    value={form.data.frequency_unit}
                                    onValueChange={(value) =>
                                        form.setData('frequency_unit', value)
                                    }
                                >
                                    <SelectTrigger id="frequency_unit">
                                        <SelectValue placeholder="Select frequency" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="daily">Daily</SelectItem>
                                        <SelectItem value="weekly">Weekly</SelectItem>
                                        <SelectItem value="monthly">Monthly</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.frequency_unit} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="frequency_interval">Every</Label>
                                <Input
                                    id="frequency_interval"
                                    type="number"
                                    min="1"
                                    value={form.data.frequency_interval}
                                    onChange={(event) =>
                                        form.setData(
                                            'frequency_interval',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.frequency_interval}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="total_authorized_cycles">
                                    Total Cycles
                                </Label>
                                <Input
                                    id="total_authorized_cycles"
                                    type="number"
                                    min="1"
                                    value={form.data.total_authorized_cycles}
                                    onChange={(event) =>
                                        form.setData(
                                            'total_authorized_cycles',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.total_authorized_cycles}
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="notes">Plan Note</Label>
                                <Textarea
                                    id="notes"
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData('notes', event.target.value)
                                    }
                                    placeholder="Optional note for the pharmacy team"
                                />
                                <InputError message={form.errors.notes} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Cycle Quantities</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {form.data.items.map((item, index) => {
                                const prescriptionItem = prescription.items[index];

                                return (
                                    <div
                                        key={item.prescription_item_id}
                                        className="rounded-lg border p-4"
                                    >
                                        <div className="mb-4 space-y-1">
                                            <div className="font-medium">
                                                {prescriptionItem.generic_name ??
                                                    prescriptionItem.item_name ??
                                                    'Medication'}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                Remaining quantity:{' '}
                                                {prescriptionItem.remaining_quantity.toFixed(
                                                    3,
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label>Quantity Per Cycle</Label>
                                                <Input
                                                    type="number"
                                                    step="0.001"
                                                    min="0.001"
                                                    value={item.quantity_per_cycle}
                                                    onChange={(event) => {
                                                        const items = [...form.data.items];
                                                        items[index] = {
                                                            ...items[index],
                                                            quantity_per_cycle:
                                                                event.target.value,
                                                        };
                                                        form.setData('items', items);
                                                    }}
                                                />
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `items.${index}.quantity_per_cycle`
                                                        ]
                                                    }
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>Line Note</Label>
                                                <Textarea
                                                    value={item.notes}
                                                    onChange={(event) => {
                                                        const items = [...form.data.items];
                                                        items[index] = {
                                                            ...items[index],
                                                            notes: event.target.value,
                                                        };
                                                        form.setData('items', items);
                                                    }}
                                                    placeholder="Optional note"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href={`/pharmacy/prescriptions/${prescription.id}`}>
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Save Treatment Plan
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
