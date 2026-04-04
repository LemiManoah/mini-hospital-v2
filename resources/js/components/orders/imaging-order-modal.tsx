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
import { type ImagingRequest, type PatientVisit } from '@/types/patient';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

export function ImagingOrderModal({
    open,
    onOpenChange,
    visit,
    imagingRequest,
    imagingModalities,
    imagingPriorities,
    imagingLateralities,
    pregnancyStatuses,
    redirectTo,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    visit: Pick<PatientVisit, 'id' | 'consultation' | 'triage'>;
    imagingRequest?: ImagingRequest | null;
    imagingModalities: { value: string; label: string }[];
    imagingPriorities: { value: string; label: string }[];
    imagingLateralities: { value: string; label: string }[];
    pregnancyStatuses: { value: string; label: string }[];
    redirectTo: 'visit' | 'consultation';
}) {
    const consultation = visit.consultation as any;
    const triage = visit.triage as any;

    const form = useForm({
        modality: imagingModalities[0]?.value ?? 'xray',
        body_part: '',
        laterality: imagingLateralities[0]?.value ?? 'na',
        clinical_history:
            consultation?.history_of_present_illness ??
            triage?.history_of_presenting_illness ??
            '',
        indication: consultation?.primary_diagnosis ?? '',
        priority: imagingPriorities[0]?.value ?? 'routine',
        requires_contrast: false,
        contrast_allergy_status: '',
        pregnancy_status: pregnancyStatuses[0]?.value ?? 'unknown',
        redirect_to: redirectTo,
    });

    useEffect(() => {
        if (open && imagingRequest) {
            form.setData({
                modality: imagingRequest.modality ?? 'xray',
                body_part: imagingRequest.body_part ?? '',
                laterality: imagingRequest.laterality ?? 'na',
                clinical_history: imagingRequest.clinical_history ?? '',
                indication: imagingRequest.indication ?? '',
                priority: imagingRequest.priority ?? 'routine',
                requires_contrast: imagingRequest.requires_contrast ?? false,
                contrast_allergy_status:
                    imagingRequest.contrast_allergy_status ?? '',
                pregnancy_status: imagingRequest.pregnancy_status ?? 'unknown',
                redirect_to: redirectTo,
            });
        } else if (open && !imagingRequest) {
            form.reset();
        }
    }, [open, imagingRequest]);

    const onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        if (imagingRequest) {
            // Edit logic
        } else {
            form.post(`/visits/${visit.id}/imaging-requests`, {
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
                        {imagingRequest
                            ? 'Edit Imaging Request'
                            : 'New Imaging Request'}
                    </DialogTitle>
                    <DialogDescription>
                        {imagingRequest
                            ? 'Update the details of this imaging request.'
                            : 'Request a new imaging study for this patient.'}
                    </DialogDescription>
                </DialogHeader>

                <form className="flex flex-col gap-4" onSubmit={onSubmit}>
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label>Modality</Label>
                            <SearchableSelect
                                inputId="imaging_modality"
                                options={imagingModalities}
                                value={form.data.modality}
                                onValueChange={(value) =>
                                    form.setData('modality', value)
                                }
                                placeholder="Select modality"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="imaging_body_part">Body Part</Label>
                            <Input
                                id="imaging_body_part"
                                value={form.data.body_part}
                                onChange={(event) =>
                                    form.setData(
                                        'body_part',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label>Laterality</Label>
                            <SearchableSelect
                                inputId="imaging_laterality"
                                options={imagingLateralities}
                                value={form.data.laterality}
                                onValueChange={(value) =>
                                    form.setData('laterality', value)
                                }
                                placeholder="Select laterality"
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="imaging_clinical_history">
                                Clinical History
                            </Label>
                            <Textarea
                                id="imaging_clinical_history"
                                rows={3}
                                value={form.data.clinical_history}
                                onChange={(event) =>
                                    form.setData(
                                        'clinical_history',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="imaging_indication">
                                Indication
                            </Label>
                            <Textarea
                                id="imaging_indication"
                                rows={3}
                                value={form.data.indication}
                                onChange={(event) =>
                                    form.setData(
                                        'indication',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label>Priority</Label>
                            <SearchableSelect
                                inputId="imaging_priority"
                                options={imagingPriorities}
                                value={form.data.priority}
                                onValueChange={(value) =>
                                    form.setData('priority', value)
                                }
                                placeholder="Select priority"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label>Pregnancy Status</Label>
                            <SearchableSelect
                                inputId="pregnancy_status"
                                options={pregnancyStatuses}
                                value={form.data.pregnancy_status}
                                onValueChange={(value) =>
                                    form.setData('pregnancy_status', value)
                                }
                                placeholder="Select pregnancy status"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="contrast_allergy_status">
                                Contrast Allergy Status
                            </Label>
                            <Input
                                id="contrast_allergy_status"
                                value={form.data.contrast_allergy_status}
                                onChange={(event) =>
                                    form.setData(
                                        'contrast_allergy_status',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>

                    <label className="flex items-center gap-3 text-sm">
                        <Checkbox
                            checked={form.data.requires_contrast}
                            onCheckedChange={(checked) =>
                                form.setData(
                                    'requires_contrast',
                                    checked === true,
                                )
                            }
                        />
                        This study requires contrast
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
                            {imagingRequest
                                ? 'Update Imaging'
                                : 'Request Imaging'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
