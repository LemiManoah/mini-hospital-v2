import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    type DrugOption,
    type PatientVisit,
    type Prescription,
} from '@/types/patient';
import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect } from 'react';

type PrescriptionDraftItem = {
    inventory_item_id: string;
    dosage: string;
    frequency: string;
    route: string;
    duration_days: string;
    quantity: string;
    instructions: string;
    is_prn: boolean;
    prn_reason: string;
    is_external_pharmacy: boolean;
};

const createPrescriptionItem = (): PrescriptionDraftItem => ({
    inventory_item_id: '',
    dosage: '',
    frequency: '',
    route: '',
    duration_days: '5',
    quantity: '1',
    instructions: '',
    is_prn: false,
    prn_reason: '',
    is_external_pharmacy: false,
});

export function PrescriptionOrderModal({
    open,
    onOpenChange,
    visit,
    prescription,
    drugOptions,
    redirectTo,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    visit: Pick<PatientVisit, 'id' | 'consultation'>;
    prescription?: Prescription | null;
    drugOptions: DrugOption[];
    redirectTo: 'visit' | 'consultation';
}) {
    const consultation = visit.consultation as any;

    const form = useForm({
        primary_diagnosis: consultation?.primary_diagnosis ?? '',
        pharmacy_notes: '',
        is_discharge_medication: false,
        is_long_term: false,
        items: [createPrescriptionItem()],
        redirect_to: redirectTo,
    });

    useEffect(() => {
        if (open && prescription) {
            form.setData({
                primary_diagnosis: prescription.primary_diagnosis ?? '',
                pharmacy_notes: prescription.pharmacy_notes ?? '',
                is_discharge_medication:
                    prescription.is_discharge_medication ?? false,
                is_long_term: prescription.is_long_term ?? false,
                items: prescription.items.map((item) => ({
                    inventory_item_id: item.inventory_item_id,
                    dosage: item.dosage ?? '',
                    frequency: item.frequency ?? '',
                    route: item.route ?? '',
                    duration_days: String(item.duration_days ?? 5),
                    quantity: String(item.quantity ?? 1),
                    instructions: item.instructions ?? '',
                    is_prn: item.is_prn ?? false,
                    prn_reason: item.prn_reason ?? '',
                    is_external_pharmacy: item.is_external_pharmacy ?? false,
                })),
                redirect_to: redirectTo,
            });
        } else if (open && !prescription) {
            form.reset();
            form.setData('items', [createPrescriptionItem()]);
        }
    }, [open, prescription]);

    const updatePrescriptionItem = <K extends keyof PrescriptionDraftItem>(
        index: number,
        field: K,
        value: PrescriptionDraftItem[K],
    ) =>
        form.setData(
            'items',
            form.data.items.map((item, itemIndex) =>
                itemIndex === index ? { ...item, [field]: value } : item,
            ),
        );

    const onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        if (prescription) {
            // Edit logic
        } else {
            form.post(`/visits/${visit.id}/prescriptions`, {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    onOpenChange(false);
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto border-none bg-white shadow-2xl sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>
                        {prescription
                            ? 'Edit Prescription'
                            : 'New Prescription'}
                    </DialogTitle>
                    <DialogDescription>
                        {prescription
                            ? 'Update the prescription details.'
                            : 'Create a new prescription for this visit.'}
                    </DialogDescription>
                </DialogHeader>

                <form className="flex flex-col gap-4" onSubmit={onSubmit}>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="primary_diagnosis">
                                Primary Diagnosis
                            </Label>
                            <Input
                                id="primary_diagnosis"
                                placeholder="e.g. Community acquired pneumonia"
                                value={form.data.primary_diagnosis}
                                onChange={(event) =>
                                    form.setData(
                                        'primary_diagnosis',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={form.errors.primary_diagnosis}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="pharmacy_notes">
                                Pharmacy Notes
                            </Label>
                            <Input
                                id="pharmacy_notes"
                                placeholder="e.g. Dispense full course if available"
                                value={form.data.pharmacy_notes}
                                onChange={(event) =>
                                    form.setData(
                                        'pharmacy_notes',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>

                    <div className="grid gap-3">
                        {form.data.items.map((item, index) => (
                            <div key={index} className="rounded-lg border p-4">
                                <div className="mb-4 flex items-center justify-between">
                                    <h3 className="font-medium">
                                        Drug {index + 1}
                                    </h3>
                                    {form.data.items.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                form.setData(
                                                    'items',
                                                    form.data.items.filter(
                                                        (_, itemIndex) =>
                                                            itemIndex !== index,
                                                    ),
                                                )
                                            }
                                        >
                                            Remove
                                        </Button>
                                    )}
                                </div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Drug</Label>
                                        <SearchableSelect
                                            inputId={`prescription_drug_${index}`}
                                            options={drugOptions.map(
                                                (drug) => ({
                                                    value: drug.id,
                                                    label: [
                                                        drug.generic_name,
                                                        drug.brand_name,
                                                        drug.strength,
                                                        drug.dosage_form,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' '),
                                                }),
                                            )}
                                            value={item.inventory_item_id}
                                            onValueChange={(drug) =>
                                                updatePrescriptionItem(
                                                    index,
                                                    'inventory_item_id',
                                                    drug,
                                                )
                                            }
                                            placeholder="Search drug"
                                            emptyMessage="No drugs found."
                                            allowClear
                                            renderOption={(option) => {
                                                const drug = drugOptions.find(
                                                    (itemOption) =>
                                                        itemOption.id ===
                                                        option.value,
                                                );

                                                return (
                                                    <div className="flex flex-col gap-0.5">
                                                        <span>
                                                            {drug?.generic_name ??
                                                                option.label}
                                                            {drug?.brand_name
                                                                ? ` (${drug.brand_name})`
                                                                : ''}
                                                        </span>
                                                        {drug?.strength ||
                                                        drug?.dosage_form ? (
                                                            <span className="text-xs text-muted-foreground">
                                                                {[
                                                                    drug.strength,
                                                                    drug.dosage_form,
                                                                ]
                                                                    .filter(
                                                                        Boolean,
                                                                    )
                                                                    .join(
                                                                        ' | ',
                                                                    )}
                                                            </span>
                                                        ) : null}
                                                    </div>
                                                );
                                            }}
                                        />
                                        <InputError
                                            message={
                                                (form.errors as any)[
                                                    `items.${index}.inventory_item_id`
                                                ]
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Dosage</Label>
                                        <Input
                                            placeholder="e.g. 500 mg"
                                            value={item.dosage}
                                            onChange={(event) =>
                                                updatePrescriptionItem(
                                                    index,
                                                    'dosage',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Frequency</Label>
                                        <Input
                                            placeholder="e.g. 1 tablet twice daily"
                                            value={item.frequency}
                                            onChange={(event) =>
                                                updatePrescriptionItem(
                                                    index,
                                                    'frequency',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Route</Label>
                                        <Input
                                            placeholder="e.g. Oral"
                                            value={item.route}
                                            onChange={(event) =>
                                                updatePrescriptionItem(
                                                    index,
                                                    'route',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Duration Days</Label>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={365}
                                            placeholder="e.g. 5"
                                            value={item.duration_days}
                                            onChange={(event) =>
                                                updatePrescriptionItem(
                                                    index,
                                                    'duration_days',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Quantity</Label>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={1000}
                                            placeholder="e.g. 10"
                                            value={item.quantity}
                                            onChange={(event) =>
                                                updatePrescriptionItem(
                                                    index,
                                                    'quantity',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Instructions</Label>
                                        <Textarea
                                            rows={3}
                                            placeholder="e.g. Take after meals and complete the full course"
                                            value={item.instructions}
                                            onChange={(event) =>
                                                updatePrescriptionItem(
                                                    index,
                                                    'instructions',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="flex flex-col gap-3 rounded-lg border p-3">
                                        <label className="flex items-center gap-3 text-sm">
                                            <Checkbox
                                                checked={item.is_prn}
                                                onCheckedChange={(checked) =>
                                                    updatePrescriptionItem(
                                                        index,
                                                        'is_prn',
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            Prescribe as needed
                                        </label>
                                        <Input
                                            placeholder="e.g. For pain or fever above 38C"
                                            value={item.prn_reason}
                                            onChange={(event) =>
                                                updatePrescriptionItem(
                                                    index,
                                                    'prn_reason',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <label className="flex items-center gap-3 text-sm">
                                            <Checkbox
                                                checked={
                                                    item.is_external_pharmacy
                                                }
                                                onCheckedChange={(checked) =>
                                                    updatePrescriptionItem(
                                                        index,
                                                        'is_external_pharmacy',
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            External pharmacy
                                        </label>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="flex items-center justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                form.setData('items', [
                                    ...form.data.items,
                                    createPrescriptionItem(),
                                ])
                            }
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add Another Drug
                        </Button>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {prescription
                                    ? 'Update Prescription'
                                    : 'Save Prescription'}
                            </Button>
                        </div>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
