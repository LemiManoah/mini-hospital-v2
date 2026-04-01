import InputError from '@/components/input-error';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type LabRequest, type PatientVisit } from '@/types/patient';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { formatMoney } from '../visit-ordering';

export function LabOrderModal({
    open,
    onOpenChange,
    visit,
    labRequest, // If provided, we are editing
    labTestOptions,
    labPriorities,
    redirectTo,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    visit: Pick<PatientVisit, 'id' | 'consultation' | 'triage'>;
    labRequest?: LabRequest | null;
    labTestOptions: Array<{
        id: string;
        test_code: string;
        test_name: string;
        category: string | null;
        base_price: number | null;
        quoted_price?: number | null;
        price_source?: string | null;
    }>;
    labPriorities: { value: string; label: string }[];
    redirectTo: 'visit' | 'consultation';
}) {
    const consultation = visit.consultation as any;
    const triage = visit.triage as any;

    const form = useForm({
        test_ids: [] as string[],
        clinical_notes:
            consultation?.history_of_present_illness ??
            triage?.history_of_presenting_illness ??
            '',
        priority: labPriorities[0]?.value ?? 'routine',
        diagnosis_code: consultation?.primary_icd10_code ?? '',
        is_stat: false,
        redirect_to: redirectTo,
    });

    useEffect(() => {
        if (open && labRequest) {
            form.setData({
                test_ids: labRequest.items.map((item) => item.test_id),
                clinical_notes: labRequest.clinical_notes ?? '',
                priority: labRequest.priority ?? 'routine',
                diagnosis_code: '', // Not stored on LabRequest model currently
                is_stat: labRequest.is_stat ?? false,
                redirect_to: redirectTo,
            });
        } else if (open && !labRequest) {
            form.reset();
        }
    }, [open, labRequest]);

    const groupedLabTests = labTestOptions.reduce<
        Record<string, typeof labTestOptions>
    >((groups, option) => {
        const key = option.category || 'Other';
        groups[key] ??= [];
        groups[key].push(option);
        return groups;
    }, {});

    const toggleLabTest = (testId: string, checked: boolean) =>
        form.setData(
            'test_ids',
            checked
                ? [...form.data.test_ids, testId]
                : form.data.test_ids.filter((value) => value !== testId),
        );

    const onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        if (labRequest) {
            // Edit logic if routes existed, but for now we only have store
            // form.put(`/visits/${visit.id}/lab-requests/${labRequest.id}`, { ... })
        } else {
            form.post(`/visits/${visit.id}/lab-requests`, {
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
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>
                        {labRequest ? 'Edit Lab Request' : 'New Lab Request'}
                    </DialogTitle>
                    <DialogDescription>
                        {labRequest
                            ? 'Update the details of this laboratory request.'
                            : 'Choose one or more active laboratory tests for this patient.'}
                    </DialogDescription>
                </DialogHeader>

                <form className="flex flex-col gap-4" onSubmit={onSubmit}>
                    <div className="flex flex-col gap-3">
                        <div className="flex flex-col gap-1">
                            <Label>Select Tests</Label>
                        </div>
                        {Object.entries(groupedLabTests).map(
                            ([category, tests]) => (
                                <div
                                    key={category}
                                    className="rounded-lg border p-3"
                                >
                                    <p className="mb-3 text-sm font-medium">
                                        {category}
                                    </p>
                                    <div className="grid gap-2 md:grid-cols-2">
                                        {tests.map((test) => (
                                            <label
                                                key={test.id}
                                                className="flex items-start gap-3 rounded-md border px-3 py-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={form.data.test_ids.includes(
                                                        test.id,
                                                    )}
                                                    onCheckedChange={(checked) =>
                                                        toggleLabTest(
                                                            test.id,
                                                            checked === true,
                                                        )
                                                    }
                                                />
                                                <span>
                                                    <span className="block font-medium">
                                                        {test.test_name}
                                                        {test.test_code
                                                            ? ` (${test.test_code})`
                                                            : ''}
                                                    </span>
                                                    <span className="block text-muted-foreground">
                                                        Quoted price:{' '}
                                                        {formatMoney(
                                                            test.quoted_price ??
                                                                test.base_price,
                                                        )}
                                                    </span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            ),
                        )}
                        <InputError message={form.errors.test_ids} />
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Priority</Label>
                            <Select
                                value={form.data.priority}
                                onValueChange={(value) =>
                                    form.setData('priority', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {labPriorities.map((priority) => (
                                        <SelectItem
                                            key={priority.value}
                                            value={priority.value}
                                        >
                                            {priority.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="diagnosis_code">
                                Diagnosis Code
                            </Label>
                            <Input
                                id="diagnosis_code"
                                value={form.data.diagnosis_code}
                                onChange={(event) =>
                                    form.setData(
                                        'diagnosis_code',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.diagnosis_code} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="lab_clinical_notes">Clinical Notes</Label>
                        <Textarea
                            id="lab_clinical_notes"
                            rows={3}
                            value={form.data.clinical_notes}
                            onChange={(event) =>
                                form.setData('clinical_notes', event.target.value)
                            }
                        />
                        <InputError message={form.errors.clinical_notes} />
                    </div>

                    <label className="flex items-center gap-3 text-sm">
                        <Checkbox
                            checked={form.data.is_stat}
                            onCheckedChange={(checked) =>
                                form.setData('is_stat', checked === true)
                            }
                        />
                        Mark this request as STAT
                    </label>

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {labRequest ? 'Update Request' : 'Create Request'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
