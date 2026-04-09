import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { ScrollArea } from '@/components/ui/scroll-area';
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
import { Search, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
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
    visit: Pick<
        PatientVisit,
        'id' | 'consultation' | 'triage' | 'labRequests' | 'lab_requests'
    >;
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
    const labRequests = visit.labRequests ?? visit.lab_requests ?? [];
    const [searchTerm, setSearchTerm] = useState('');

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

    const filteredTests = useMemo(() => {
        if (!searchTerm) return labTestOptions;
        const term = searchTerm.toLowerCase();
        return labTestOptions.filter(
            (t) =>
                t.test_name.toLowerCase().includes(term) ||
                t.test_code.toLowerCase().includes(term) ||
                t.category?.toLowerCase().includes(term),
        );
    }, [labTestOptions, searchTerm]);

    const groupedLabTests = useMemo(() => {
        return filteredTests.reduce<Record<string, typeof labTestOptions>>(
            (groups, option) => {
                const key = option.category || 'Other';
                groups[key] ??= [];
                groups[key].push(option);
                return groups;
            },
            {},
        );
    }, [filteredTests]);

    const selectedTestsList = useMemo(() => {
        return labTestOptions.filter((t) => form.data.test_ids.includes(t.id));
    }, [labTestOptions, form.data.test_ids]);
    const pendingLabTestIds = useMemo(
        () =>
            new Set(
                labRequests.flatMap((request) =>
                    request.id === labRequest?.id
                        ? []
                        : request.items
                              .filter((item) => item.status === 'pending')
                              .map((item) => item.test_id ?? item.test?.id)
                              .filter((id): id is string => Boolean(id)),
                ),
            ),
        [labRequest?.id, labRequests],
    );
    const hasPendingSelectedTests = form.data.test_ids.some((testId) =>
        pendingLabTestIds.has(testId),
    );

    const toggleLabTest = (testId: string, checked: boolean) =>
        form.setData(
            'test_ids',
            checked
                ? [...form.data.test_ids, testId]
                : form.data.test_ids.filter((value) => value !== testId),
        );

    const removeTest = (testId: string) => {
        form.setData(
            'test_ids',
            form.data.test_ids.filter((id) => id !== testId),
        );
    };

    const onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        if (labRequest) {
            form.patch(`/visits/${visit.id}/lab-requests/${labRequest.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    onOpenChange(false);
                },
            });
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
            <DialogContent className="max-h-[95vh] overflow-y-auto border-none bg-white p-0 shadow-2xl sm:max-w-5xl">
                <DialogHeader className="p-6 pb-2">
                    <DialogTitle className="text-xl font-bold">
                        {labRequest ? 'Edit Lab Request' : 'New Lab Request'}
                    </DialogTitle>
                    <DialogDescription>
                        {labRequest
                            ? 'Update the details of this laboratory request.'
                            : 'Search and select laboratory tests for this patient.'}
                    </DialogDescription>
                </DialogHeader>

                <form
                    className="flex flex-col"
                    onSubmit={onSubmit}
                >
                    <div className="grid lg:grid-cols-[minmax(0,1fr)_300px]">
                        {/* Left Side: Test Selection */}
                        <div className="border-b lg:border-r lg:border-b-0">
                            <div className="border-b bg-zinc-50/50 p-4">
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        placeholder="Search by test name, code or category..."
                                        className="bg-white pl-9"
                                        value={searchTerm}
                                        onChange={(e) =>
                                            setSearchTerm(e.target.value)
                                        }
                                    />
                                    {searchTerm && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="absolute top-1/2 right-1 h-7 w-7 -translate-y-1/2"
                                            onClick={() => setSearchTerm('')}
                                        >
                                            <X className="h-3 w-3" />
                                        </Button>
                                    )}
                                </div>
                            </div>

                            <ScrollArea className="max-h-[48vh] p-4 lg:max-h-[68vh]">
                                <div className="space-y-6">
                                    {Object.entries(groupedLabTests).length ===
                                    0 ? (
                                        <div className="py-12 text-center text-muted-foreground">
                                            No tests found matching "
                                            {searchTerm}"
                                        </div>
                                    ) : (
                                        Object.entries(groupedLabTests).map(
                                            ([category, tests]) => (
                                                <div
                                                    key={category}
                                                    className="space-y-3"
                                                >
                                                    <h3 className="sticky top-0 z-10 bg-white py-1 text-xs font-bold tracking-wider text-zinc-500 uppercase">
                                                        {category}
                                                    </h3>
                                                    <div className="grid gap-2 sm:grid-cols-2">
                                                        {tests.map((test) => (
                                                            <label
                                                                key={test.id}
                                                                className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm transition-colors hover:bg-zinc-50 ${
                                                                    form.data.test_ids.includes(
                                                                        test.id,
                                                                    )
                                                                        ? 'border-primary bg-primary/5'
                                                                        : ''
                                                                }`}
                                                            >
                                                                <Checkbox
                                                                    checked={form.data.test_ids.includes(
                                                                        test.id,
                                                                    )}
                                                                    onCheckedChange={(
                                                                        checked,
                                                                    ) =>
                                                                        toggleLabTest(
                                                                            test.id,
                                                                            checked ===
                                                                                true,
                                                                        )
                                                                    }
                                                                    className="mt-0.5"
                                                                />
                                                                <div className="min-w-0 flex-1">
                                                                    <div className="truncate font-medium">
                                                                        {
                                                                            test.test_name
                                                                        }
                                                                    </div>
                                                                    <div className="mt-1 flex items-center justify-between text-[11px] text-muted-foreground">
                                                                        <span>
                                                                            {
                                                                                test.test_code
                                                                            }
                                                                        </span>
                                                                        <span className="font-semibold text-zinc-700">
                                                                            {formatMoney(
                                                                                test.quoted_price ??
                                                                                    test.base_price,
                                                                            )}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        ))}
                                                    </div>
                                                </div>
                                            ),
                                        )
                                    )}
                                </div>
                            </ScrollArea>
                        </div>

                        {/* Right Side: Request Details */}
                        <div className="bg-zinc-50/30 p-6">
                            <div className="space-y-6">
                                <div className="space-y-3">
                                    <Label className="text-xs font-bold tracking-wider text-zinc-500 uppercase">
                                        Selected Tests (
                                        {form.data.test_ids.length})
                                    </Label>
                                    <div className="flex flex-wrap gap-2">
                                        {selectedTestsList.length === 0 ? (
                                            <p className="text-sm text-muted-foreground italic">
                                                No tests selected yet.
                                            </p>
                                        ) : (
                                            selectedTestsList.map((t) => (
                                                <Badge
                                                    key={t.id}
                                                    variant="secondary"
                                                    className="gap-1 py-1 pr-1 pl-2"
                                                >
                                                    {t.test_name}
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-4 w-4 rounded-full hover:bg-zinc-200"
                                                        onClick={() =>
                                                            removeTest(t.id)
                                                        }
                                                    >
                                                        <X className="h-2 w-2" />
                                                    </Button>
                                                </Badge>
                                            ))
                                        )}
                                    </div>
                                    <InputError message={form.errors.test_ids} />
                                    {hasPendingSelectedTests ? (
                                        <p className="text-sm text-amber-700">
                                            One or more selected tests already
                                            have pending orders for this visit.
                                        </p>
                                    ) : null}
                                </div>

                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label>Priority</Label>
                                        <Select
                                            value={form.data.priority}
                                            onValueChange={(value) =>
                                                form.setData('priority', value)
                                            }
                                        >
                                            <SelectTrigger className="bg-white">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent className="bg-white">
                                                {labPriorities.map(
                                                    (priority) => (
                                                        <SelectItem
                                                            key={
                                                                priority.value
                                                            }
                                                            value={
                                                                priority.value
                                                            }
                                                        >
                                                            {priority.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="diagnosis_code">
                                            Diagnosis Code
                                        </Label>
                                        <Input
                                            id="diagnosis_code"
                                            className="bg-white"
                                            value={form.data.diagnosis_code}
                                            onChange={(e) =>
                                                form.setData(
                                                    'diagnosis_code',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.diagnosis_code}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="lab_clinical_notes">
                                            Clinical Notes
                                        </Label>
                                        <Textarea
                                            id="lab_clinical_notes"
                                            className="bg-white"
                                            rows={4}
                                            value={form.data.clinical_notes}
                                            onChange={(e) =>
                                                form.setData(
                                                    'clinical_notes',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.clinical_notes}
                                        />
                                    </div>

                                    <label className="flex cursor-pointer items-center gap-3 text-sm font-medium">
                                        <Checkbox
                                            checked={form.data.is_stat}
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'is_stat',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        Mark as STAT (Urgent)
                                    </label>
                                </div>
                            </div>

                            <div className="mt-6 flex flex-col gap-3 border-t bg-white pt-6">
                                <div className="flex justify-between text-sm font-medium">
                                    <span className="text-muted-foreground">
                                        Total Estimate:
                                    </span>
                                    <span>
                                        {formatMoney(
                                            selectedTestsList.reduce(
                                                (sum, t) =>
                                                    sum +
                                                    (t.quoted_price ??
                                                        t.base_price ??
                                                        0),
                                                0,
                                            ),
                                        )}
                                    </span>
                                </div>
                                <div className="flex gap-3">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="flex-1"
                                        onClick={() => onOpenChange(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        className="flex-1"
                                        disabled={
                                            form.processing ||
                                            form.data.test_ids.length === 0 ||
                                            hasPendingSelectedTests
                                        }
                                    >
                                        {labRequest
                                            ? 'Update Request'
                                            : 'Create Request'}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
