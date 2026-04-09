import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { type LaboratoryRequestItem } from '@/types/laboratory';
import { useForm } from '@inertiajs/react';

export function EnterResultDialog({
    item,
    open,
    onOpenChange,
    redirectTo,
}: {
    item: LaboratoryRequestItem;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    redirectTo: string;
}) {
    const resultEntry = item.resultEntry ?? item.result_entry ?? null;
    const resultValues = resultEntry?.values ?? [];
    const resultParameters = item.test?.result_parameters ?? [];
    const resultOptions = item.test?.result_options ?? [];
    const resultType = item.test?.result_capture_type ?? 'free_entry';
    const form = useForm({
        result_notes: resultEntry?.result_notes ?? '',
        free_entry_value:
            resultValues[0]?.value_text ?? resultValues[0]?.display_value ?? '',
        selected_option_label:
            resultValues[0]?.value_text ?? resultValues[0]?.display_value ?? '',
        parameter_values: resultParameters.map((parameter) => ({
            lab_test_result_parameter_id: parameter.id ?? '',
            value:
                resultValues.find(
                    (value) =>
                        value.lab_test_result_parameter_id === parameter.id,
                )?.display_value ?? '',
        })),
        redirect_to: redirectTo,
    });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Enter Results</DialogTitle>
                    <DialogDescription>
                        {item.test?.test_name ?? 'Lab test'} for accession{' '}
                        {item.specimen?.accession_number ?? 'not assigned'}.
                    </DialogDescription>
                </DialogHeader>

                <form
                    className="flex flex-col gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(`/laboratory/request-items/${item.id}/results`, {
                            preserveScroll: true,
                            onSuccess: () => onOpenChange(false),
                        });
                    }}
                >
                    {resultType === 'free_entry' ? (
                        <div className="grid gap-2">
                            <Label htmlFor="free_entry_value">Result</Label>
                            <Textarea
                                id="free_entry_value"
                                rows={6}
                                value={form.data.free_entry_value}
                                onChange={(event) =>
                                    form.setData(
                                        'free_entry_value',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={form.errors.free_entry_value}
                            />
                        </div>
                    ) : null}

                    {resultType === 'defined_option' ? (
                        <div className="grid gap-2">
                            <Label htmlFor="selected_option_label">
                                Result Option
                            </Label>
                            <Select
                                value={form.data.selected_option_label}
                                onValueChange={(value) =>
                                    form.setData('selected_option_label', value)
                                }
                            >
                                <SelectTrigger id="selected_option_label">
                                    <SelectValue placeholder="Choose an option" />
                                </SelectTrigger>
                                <SelectContent>
                                    {resultOptions.map((option) => (
                                        <SelectItem
                                            key={option.id ?? option.label}
                                            value={option.label}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={form.errors.selected_option_label}
                            />
                        </div>
                    ) : null}

                    {resultType === 'parameter_panel' ? (
                        <div className="flex flex-col gap-4">
                            {resultParameters.map((parameter, index) => (
                                <div
                                    key={parameter.id ?? parameter.label}
                                    className="rounded-lg border p-4"
                                >
                                    <p className="font-medium">
                                        {parameter.label}
                                    </p>
                                    <p className="mb-3 text-sm text-muted-foreground">
                                        {parameter.unit
                                            ? `Unit: ${parameter.unit}`
                                            : 'No unit'}
                                        {' | '}
                                        {parameter.reference_range
                                            ? `Reference: ${parameter.reference_range}`
                                            : 'No reference'}
                                    </p>
                                    <Input
                                        type={
                                            parameter.value_type === 'numeric'
                                                ? 'number'
                                                : 'text'
                                        }
                                        step={
                                            parameter.value_type === 'numeric'
                                                ? '0.01'
                                                : undefined
                                        }
                                        value={
                                            form.data.parameter_values[index]
                                                ?.value ?? ''
                                        }
                                        onChange={(event) => {
                                            const nextValues = [
                                                ...form.data.parameter_values,
                                            ];
                                            nextValues[index] = {
                                                ...nextValues[index],
                                                value: event.target.value,
                                            };
                                            form.setData(
                                                'parameter_values',
                                                nextValues,
                                            );
                                        }}
                                    />
                                </div>
                            ))}
                            <InputError
                                message={form.errors.parameter_values}
                            />
                        </div>
                    ) : null}

                    <div className="grid gap-2">
                        <Label htmlFor="result_notes">Bench Notes</Label>
                        <Textarea
                            id="result_notes"
                            rows={4}
                            value={form.data.result_notes}
                            onChange={(event) =>
                                form.setData('result_notes', event.target.value)
                            }
                        />
                        <InputError message={form.errors.result_notes} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Save Results
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
