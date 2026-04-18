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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type LaboratoryRequestItem } from '@/types/laboratory';
import { useForm } from '@inertiajs/react';
import { resultValueDisplay } from './queue-utils';

export function ReviewResultDialog({
    item,
    open,
    onOpenChange,
    redirectTo,
    labReleasePolicy,
}: {
    item: LaboratoryRequestItem;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    redirectTo: string;
    labReleasePolicy: {
        require_review_before_release: boolean;
        require_approval_before_release: boolean;
    };
}) {
    const resultEntry = item.resultEntry ?? item.result_entry ?? null;
    const values = resultEntry?.values ?? [];
    const form = useForm({
        review_notes: resultEntry?.review_notes ?? '',
        approval_notes: resultEntry?.approval_notes ?? '',
        redirect_to: redirectTo,
    });
    const requiresApproval = labReleasePolicy.require_approval_before_release;
    const submitUrl = requiresApproval
        ? `/laboratory/request-items/${item.id}/approve`
        : `/laboratory/request-items/${item.id}/review`;
    const dialogTitle = requiresApproval
        ? 'Review and Release Results'
        : 'Review Results';
    const dialogDescription = requiresApproval
        ? 'Confirm the entered result and release it in one step.'
        : 'Confirm the entered result and release it directly from the review step.';
    const submitLabel = requiresApproval
        ? 'Review and Release'
        : 'Review and Release';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{dialogTitle}</DialogTitle>
                    <DialogDescription>{dialogDescription}</DialogDescription>
                </DialogHeader>

                <div className="rounded-lg border p-4">
                    <p className="font-medium">Entered Result</p>
                    <div className="mt-3 flex flex-col gap-3 text-sm">
                        {values.length === 0 ? (
                            <p className="text-muted-foreground">
                                No result values are available.
                            </p>
                        ) : (
                            values.map((value) => (
                                <div
                                    key={value.id}
                                    className="flex items-start justify-between gap-4"
                                >
                                    <span className="text-muted-foreground">
                                        {value.label}
                                    </span>
                                    <span className="text-right font-medium">
                                        {resultValueDisplay(value)}
                                        {value.unit ? ` ${value.unit}` : ''}
                                    </span>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="review_notes">Review Notes</Label>
                        <Textarea
                            id="review_notes"
                            rows={4}
                            value={form.data.review_notes}
                            onChange={(event) =>
                                form.setData('review_notes', event.target.value)
                            }
                        />
                        <InputError message={form.errors.review_notes} />
                    </div>
                    {requiresApproval ? (
                        <div className="grid gap-2">
                            <Label htmlFor="approval_notes">
                                Release Notes
                            </Label>
                            <Textarea
                                id="approval_notes"
                                rows={4}
                                value={form.data.approval_notes}
                                onChange={(event) =>
                                    form.setData(
                                        'approval_notes',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.approval_notes} />
                        </div>
                    ) : null}
                </div>

                <DialogFooter className="justify-between sm:justify-between">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Close
                    </Button>
                    <Button
                        type="button"
                        disabled={form.processing}
                        onClick={() =>
                            form.post(submitUrl, {
                                preserveScroll: true,
                                onSuccess: () => onOpenChange(false),
                            })
                        }
                    >
                        {submitLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
