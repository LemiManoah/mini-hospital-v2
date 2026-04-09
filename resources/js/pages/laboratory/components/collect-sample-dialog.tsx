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

export function CollectSampleDialog({
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
    const options = item.test?.available_specimens ?? [];
    const form = useForm({
        specimen_type_id:
            item.specimen?.specimen_type_id ?? options[0]?.id ?? '',
        outside_sample_origin: item.specimen?.outside_sample_origin ?? '',
        notes: item.specimen?.notes ?? '',
        redirect_to: redirectTo,
    });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Pick Sample</DialogTitle>
                    <DialogDescription>
                        {item.test?.test_name ?? 'Lab test'} will get its
                        collected date and time automatically when you save.
                    </DialogDescription>
                </DialogHeader>

                <form
                    className="flex flex-col gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(
                            `/laboratory/request-items/${item.id}/collect-sample`,
                            {
                                preserveScroll: true,
                                onSuccess: () => onOpenChange(false),
                            },
                        );
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="specimen_type_id">Specimen</Label>
                        <Select
                            value={form.data.specimen_type_id}
                            onValueChange={(value) =>
                                form.setData('specimen_type_id', value)
                            }
                        >
                            <SelectTrigger id="specimen_type_id">
                                <SelectValue placeholder="Choose a specimen" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.map((option) => (
                                    <SelectItem key={option.id} value={option.id}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.specimen_type_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="outside_sample_origin">
                            Outside Sample Source
                        </Label>
                        <Input
                            id="outside_sample_origin"
                            value={form.data.outside_sample_origin}
                            onChange={(event) =>
                                form.setData(
                                    'outside_sample_origin',
                                    event.target.value,
                                )
                            }
                            placeholder="Referral facility or external collection point"
                        />
                        <InputError
                            message={form.errors.outside_sample_origin}
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Collection Notes</Label>
                        <Textarea
                            id="notes"
                            rows={4}
                            value={form.data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                        />
                        <InputError message={form.errors.notes} />
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
                            Save Sample
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
