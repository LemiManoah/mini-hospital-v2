import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type ConsultationTariffFormProps = {
    action: string;
    method: 'post' | 'put';
    visitTypeOptions: { value: string; label: string }[];
    consultationTypeOptions: { value: string; label: string }[];
    facilityServiceOptions: { value: string; label: string }[];
    initialValues?: {
        visit_type?: string | null;
        consultation_type?: string | null;
        facility_service_id?: string | null;
        is_active?: boolean;
    };
    submitLabel: string;
};

export function ConsultationTariffForm({
    action,
    method,
    visitTypeOptions,
    consultationTypeOptions,
    facilityServiceOptions,
    initialValues,
    submitLabel,
}: ConsultationTariffFormProps) {
    const form = useForm({
        visit_type: initialValues?.visit_type ?? 'all',
        consultation_type:
            initialValues?.consultation_type ??
            consultationTypeOptions[0]?.value ??
            '',
        facility_service_id: initialValues?.facility_service_id ?? '',
        is_active: initialValues?.is_active ?? true,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (method === 'put') {
            form.put(action);

            return;
        }

        form.post(action);
    };

    return (
        <form className="space-y-6" onSubmit={submit}>
            <div className="grid gap-4 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label>Visit Type Scope</Label>
                    <Select
                        value={form.data.visit_type}
                        onValueChange={(value) =>
                            form.setData('visit_type', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Choose visit type scope" />
                        </SelectTrigger>
                        <SelectContent>
                            {visitTypeOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.visit_type} />
                </div>

                <div className="grid gap-2">
                    <Label>Consultation Type</Label>
                    <Select
                        value={form.data.consultation_type}
                        onValueChange={(value) =>
                            form.setData('consultation_type', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Choose consultation type" />
                        </SelectTrigger>
                        <SelectContent>
                            {consultationTypeOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.consultation_type} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label>Billing Tariff Service</Label>
                <Select
                    value={form.data.facility_service_id}
                    onValueChange={(value) =>
                        form.setData('facility_service_id', value)
                    }
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Choose billable facility service" />
                    </SelectTrigger>
                    <SelectContent>
                        {facilityServiceOptions.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={form.errors.facility_service_id} />
            </div>

            <label className="flex items-center gap-3 text-sm">
                <Checkbox
                    checked={form.data.is_active}
                    onCheckedChange={(checked) =>
                        form.setData('is_active', checked === true)
                    }
                />
                Keep this consultation tariff active
            </label>

            <div className="flex justify-end">
                <Button type="submit" disabled={form.processing}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
