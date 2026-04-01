import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    type LaboratoryRequestItemPageProps,
    type LaboratoryResultEntry,
    type LaboratoryResultValue,
} from '@/types/laboratory';
import { Head, Link, router, useForm } from '@inertiajs/react';

type ResultParameterDraft = {
    lab_test_result_parameter_id: string;
    value: string;
};

const formatMoney = (amount: number | null | undefined): string =>
    new Intl.NumberFormat('en-UG', {
        style: 'currency',
        currency: 'UGX',
        maximumFractionDigits: 0,
    }).format(amount ?? 0);

const formatDateTime = (value: string | null | undefined): string =>
    value ? new Date(value).toLocaleString() : 'Not yet recorded';

const labelize = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';

const actorName = (
    actor?: { first_name: string; last_name: string } | null,
): string =>
    actor ? `${actor.first_name} ${actor.last_name}` : 'Not recorded';

const workflowVariant = (
    workflowStage: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (workflowStage === 'approved') return 'default';
    if (workflowStage === 'reviewed') return 'secondary';
    if (workflowStage === 'cancelled') return 'destructive';

    return 'outline';
};

const resultValueDisplay = (value: LaboratoryResultValue): string =>
    value.display_value ?? value.value_text ?? `${value.value_numeric ?? ''}`;

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-muted-foreground">{label}</p>
            <p className="font-medium">{value}</p>
        </div>
    );
}

export default function LaboratoryRequestItemShow({
    labRequestItem,
}: LaboratoryRequestItemPageProps) {
    const patient = labRequestItem.request?.visit?.patient ?? null;
    const resultEntry: LaboratoryResultEntry | null =
        labRequestItem.resultEntry ?? labRequestItem.result_entry ?? null;
    const resultValues = resultEntry?.values ?? [];
    const resultOptions = labRequestItem.test?.result_options ?? [];
    const resultParameters = labRequestItem.test?.result_parameters ?? [];
    const resultType = labRequestItem.test?.result_capture_type ?? 'free_entry';
    const variance =
        (labRequestItem.price ?? 0) - (labRequestItem.actual_cost ?? 0);
    const isApproved = labRequestItem.workflow_stage === 'approved';
    const canReview =
        labRequestItem.workflow_stage === 'result_entered' ||
        labRequestItem.workflow_stage === 'reviewed';
    const canApprove =
        labRequestItem.workflow_stage === 'reviewed' ||
        labRequestItem.workflow_stage === 'approved';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Laboratory', href: '/laboratory/incoming-investigations' },
        {
            title: 'Incoming Lab Investigations Queue',
            href: '/laboratory/incoming-investigations',
        },
        {
            title: labRequestItem.test?.test_name ?? 'Request Item',
            href: `/laboratory/request-items/${labRequestItem.id}`,
        },
    ];

    const resultForm = useForm({
        result_notes: resultEntry?.result_notes ?? '',
        free_entry_value:
            resultValues[0]?.value_text ?? resultValues[0]?.display_value ?? '',
        selected_option_label:
            resultValues[0]?.value_text ?? resultValues[0]?.display_value ?? '',
        parameter_values: resultParameters.map(
            (parameter): ResultParameterDraft => ({
                lab_test_result_parameter_id: parameter.id ?? '',
                value:
                    resultValues.find(
                        (value) =>
                            value.lab_test_result_parameter_id === parameter.id,
                    )?.display_value ?? '',
            }),
        ),
    });

    const reviewForm = useForm({
        review_notes: resultEntry?.review_notes ?? '',
    });
    const approvalForm = useForm({
        approval_notes: resultEntry?.approval_notes ?? '',
    });
    const consumableForm = useForm({
        consumable_name: '',
        unit_label: '',
        quantity: '1',
        unit_cost: '0',
        used_at: '',
        notes: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Lab Workflow ${labRequestItem.test?.test_name ?? ''}`}
            />
            <div className="m-4 flex flex-col gap-6">
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div className="flex flex-col gap-2">
                                <div className="flex flex-wrap items-center gap-2">
                                    <CardTitle>
                                        {labRequestItem.test?.test_name ??
                                            'Lab test'}
                                    </CardTitle>
                                    <Badge
                                        variant={workflowVariant(
                                            labRequestItem.workflow_stage,
                                        )}
                                    >
                                        {labelize(
                                            labRequestItem.workflow_stage,
                                        )}
                                    </Badge>
                                    <Badge variant="outline">
                                        {labelize(
                                            labRequestItem.request?.priority,
                                        )}
                                    </Badge>
                                </div>
                                <CardDescription>
                                    {patient
                                        ? `${patient.first_name} ${patient.last_name}`
                                        : 'Unknown patient'}{' '}
                                    | Visit{' '}
                                    {labRequestItem.request?.visit
                                        ?.visit_number ?? 'N/A'}{' '}
                                    | MRN {patient?.patient_number ?? 'N/A'}
                                </CardDescription>
                                <p className="text-sm text-muted-foreground">
                                    {labRequestItem.test?.test_code ?? 'N/A'} |{' '}
                                    {labRequestItem.test?.category ??
                                        'Uncategorized'}{' '}
                                    |{' '}
                                    {labRequestItem.test?.specimen_type ??
                                        'Specimen not set'}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" asChild>
                                    <Link href="/laboratory/incoming-investigations">
                                        Back to Queue
                                    </Link>
                                </Button>
                                {labRequestItem.workflow_stage === 'pending' ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        asChild
                                    >
                                        <Link href="/laboratory/incoming-investigations">
                                            Pick Sample in Queue
                                        </Link>
                                    </Button>
                                ) : null}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <SummaryRow
                            label="Sample Picked"
                            value={formatDateTime(labRequestItem.received_at)}
                        />
                        <SummaryRow
                            label="Result Entered"
                            value={formatDateTime(
                                labRequestItem.result_entered_at,
                            )}
                        />
                        <SummaryRow
                            label="Reviewed"
                            value={formatDateTime(labRequestItem.reviewed_at)}
                        />
                        <SummaryRow
                            label="Approved"
                            value={formatDateTime(labRequestItem.approved_at)}
                        />
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.45fr_0.95fr]">
                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Result Entry</CardTitle>
                                <CardDescription>
                                    Save the bench result in the configured
                                    format for this test.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        resultForm.post(
                                            `/laboratory/request-items/${labRequestItem.id}/results`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className="flex flex-col gap-4"
                                >
                                    {resultType === 'free_entry' ? (
                                        <div className="grid gap-2">
                                            <Label htmlFor="free_entry_value">
                                                Result
                                            </Label>
                                            <Textarea
                                                id="free_entry_value"
                                                rows={6}
                                                value={
                                                    resultForm.data
                                                        .free_entry_value
                                                }
                                                onChange={(event) =>
                                                    resultForm.setData(
                                                        'free_entry_value',
                                                        event.target.value,
                                                    )
                                                }
                                                disabled={isApproved}
                                            />
                                            <InputError
                                                message={
                                                    resultForm.errors
                                                        .free_entry_value
                                                }
                                            />
                                        </div>
                                    ) : null}

                                    {resultType === 'defined_option' ? (
                                        <div className="grid gap-2">
                                            <Label htmlFor="selected_option_label">
                                                Result Option
                                            </Label>
                                            <Select
                                                value={
                                                    resultForm.data
                                                        .selected_option_label
                                                }
                                                onValueChange={(value) =>
                                                    resultForm.setData(
                                                        'selected_option_label',
                                                        value,
                                                    )
                                                }
                                                disabled={isApproved}
                                            >
                                                <SelectTrigger id="selected_option_label">
                                                    <SelectValue placeholder="Choose a result option" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        {resultOptions.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.id ??
                                                                        option.label
                                                                    }
                                                                    value={
                                                                        option.label
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    resultForm.errors
                                                        .selected_option_label
                                                }
                                            />
                                        </div>
                                    ) : null}

                                    {resultType === 'parameter_panel' ? (
                                        <div className="flex flex-col gap-4">
                                            {resultParameters.map(
                                                (parameter, index) => (
                                                    <div
                                                        key={
                                                            parameter.id ??
                                                            parameter.label
                                                        }
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
                                                                ? `Ref: ${parameter.reference_range}`
                                                                : 'No ref'}
                                                            {parameter.gender &&
                                                            parameter.gender !==
                                                                'both'
                                                                ? ` | Gender: ${labelize(parameter.gender)}`
                                                                : ''}
                                                            {parameter.age_min !==
                                                                null ||
                                                            parameter.age_max !==
                                                                null
                                                                ? ` | Age: ${parameter.age_min ?? 0}-${parameter.age_max ?? '+'}`
                                                                : ''}
                                                        </p>
                                                        <Input
                                                            type={
                                                                parameter.value_type ===
                                                                'numeric'
                                                                    ? 'number'
                                                                    : 'text'
                                                            }
                                                            step={
                                                                parameter.value_type ===
                                                                'numeric'
                                                                    ? '0.01'
                                                                    : undefined
                                                            }
                                                            value={
                                                                resultForm.data
                                                                    .parameter_values[
                                                                    index
                                                                ]?.value ?? ''
                                                            }
                                                            onChange={(event) =>
                                                                resultForm.setData(
                                                                    'parameter_values',
                                                                    resultForm.data.parameter_values.map(
                                                                        (
                                                                            draft,
                                                                            draftIndex,
                                                                        ) =>
                                                                            draftIndex ===
                                                                            index
                                                                                ? {
                                                                                      ...draft,
                                                                                      value: event
                                                                                          .target
                                                                                          .value,
                                                                                  }
                                                                                : draft,
                                                                    ),
                                                                )
                                                            }
                                                            disabled={
                                                                isApproved
                                                            }
                                                        />
                                                    </div>
                                                ),
                                            )}
                                            <InputError
                                                message={
                                                    resultForm.errors
                                                        .parameter_values
                                                }
                                            />
                                        </div>
                                    ) : null}

                                    <div className="grid gap-2">
                                        <Label htmlFor="result_notes">
                                            Bench Notes
                                        </Label>
                                        <Textarea
                                            id="result_notes"
                                            rows={4}
                                            value={resultForm.data.result_notes}
                                            onChange={(event) =>
                                                resultForm.setData(
                                                    'result_notes',
                                                    event.target.value,
                                                )
                                            }
                                            disabled={isApproved}
                                        />
                                        <InputError
                                            message={
                                                resultForm.errors.result_notes
                                            }
                                        />
                                    </div>

                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            disabled={
                                                isApproved ||
                                                resultForm.processing
                                            }
                                        >
                                            Save Results
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Review and Approval</CardTitle>
                                <CardDescription>
                                    Approval releases the result for clinicians.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-6">
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        reviewForm.post(
                                            `/laboratory/request-items/${labRequestItem.id}/review`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className="flex flex-col gap-3 rounded-lg border p-4"
                                >
                                    <p className="text-sm text-muted-foreground">
                                        Reviewed by:{' '}
                                        {actorName(resultEntry?.reviewedBy)} |{' '}
                                        {formatDateTime(
                                            resultEntry?.reviewed_at,
                                        )}
                                    </p>
                                    <Textarea
                                        rows={3}
                                        value={reviewForm.data.review_notes}
                                        onChange={(event) =>
                                            reviewForm.setData(
                                                'review_notes',
                                                event.target.value,
                                            )
                                        }
                                        disabled={!canReview || isApproved}
                                        placeholder="Optional review notes"
                                    />
                                    <InputError
                                        message={reviewForm.errors.review_notes}
                                    />
                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={
                                                !canReview ||
                                                isApproved ||
                                                reviewForm.processing
                                            }
                                        >
                                            Mark as Reviewed
                                        </Button>
                                    </div>
                                </form>

                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        approvalForm.post(
                                            `/laboratory/request-items/${labRequestItem.id}/approve`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className="flex flex-col gap-3 rounded-lg border p-4"
                                >
                                    <p className="text-sm text-muted-foreground">
                                        Approved by:{' '}
                                        {actorName(resultEntry?.approvedBy)} |{' '}
                                        {formatDateTime(
                                            resultEntry?.approved_at,
                                        )}
                                    </p>
                                    <Textarea
                                        rows={3}
                                        value={approvalForm.data.approval_notes}
                                        onChange={(event) =>
                                            approvalForm.setData(
                                                'approval_notes',
                                                event.target.value,
                                            )
                                        }
                                        disabled={!canApprove}
                                        placeholder="Optional approval notes"
                                    />
                                    <InputError
                                        message={
                                            approvalForm.errors.approval_notes
                                        }
                                    />
                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            disabled={
                                                !canApprove ||
                                                approvalForm.processing
                                            }
                                        >
                                            Approve and Release
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Released Result</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {labRequestItem.result_visible &&
                                resultValues.length ? (
                                    resultValues.map((value) => (
                                        <div
                                            key={value.id}
                                            className="rounded-lg border p-4"
                                        >
                                            <p className="font-medium">
                                                {value.label}
                                            </p>
                                            <p className="text-lg font-semibold">
                                                {resultValueDisplay(value)}
                                                {value.unit
                                                    ? ` ${value.unit}`
                                                    : ''}
                                            </p>
                                            {value.reference_range ? (
                                                <p className="text-sm text-muted-foreground">
                                                    Ref: {value.reference_range}
                                                    {value.gender &&
                                                    value.gender !== 'both'
                                                        ? ` | Gender: ${labelize(value.gender)}`
                                                        : ''}
                                                    {value.age_min !== null ||
                                                    value.age_max !== null
                                                        ? ` | Age: ${value.age_min ?? 0}-${value.age_max ?? '+'}`
                                                        : ''}
                                                </p>
                                            ) : null}
                                        </div>
                                    ))
                                ) : (
                                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                        Approved results will appear here after
                                        release.
                                    </div>
                                )}
                                {resultEntry?.result_notes ? (
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Bench Notes
                                        </p>
                                        <p className="text-sm">
                                            {resultEntry.result_notes}
                                        </p>
                                    </div>
                                ) : null}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Consumables Used</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        consumableForm.post(
                                            `/laboratory/request-items/${labRequestItem.id}/consumables`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className="grid gap-4 rounded-lg border p-4 md:grid-cols-2"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="consumable_name">
                                            Consumable Name
                                        </Label>
                                        <Input
                                            id="consumable_name"
                                            value={
                                                consumableForm.data
                                                    .consumable_name
                                            }
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'consumable_name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors
                                                    .consumable_name
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="unit_label">Unit</Label>
                                        <Input
                                            id="unit_label"
                                            value={
                                                consumableForm.data.unit_label
                                            }
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'unit_label',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.unit_label
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="quantity">
                                            Quantity
                                        </Label>
                                        <Input
                                            id="quantity"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={consumableForm.data.quantity}
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'quantity',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.quantity
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="unit_cost">
                                            Unit Cost
                                        </Label>
                                        <Input
                                            id="unit_cost"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={
                                                consumableForm.data.unit_cost
                                            }
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'unit_cost',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.unit_cost
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="used_at">Used At</Label>
                                        <Input
                                            id="used_at"
                                            type="datetime-local"
                                            value={consumableForm.data.used_at}
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'used_at',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.used_at
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="notes">Notes</Label>
                                        <Textarea
                                            id="notes"
                                            rows={4}
                                            value={consumableForm.data.notes}
                                            onChange={(event) =>
                                                consumableForm.setData(
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                consumableForm.errors.notes
                                            }
                                        />
                                    </div>
                                    <div className="flex justify-end md:col-span-2">
                                        <Button
                                            type="submit"
                                            disabled={consumableForm.processing}
                                        >
                                            Add Consumable Usage
                                        </Button>
                                    </div>
                                </form>

                                {(labRequestItem.consumables ?? []).length ? (
                                    <div className="flex flex-col gap-3">
                                        {labRequestItem.consumables?.map(
                                            (usage) => (
                                                <div
                                                    key={usage.id}
                                                    className="rounded-lg border p-4"
                                                >
                                                    <div className="flex flex-col gap-3 lg:flex-row lg:justify-between">
                                                        <div className="flex flex-col gap-1">
                                                            <p className="font-medium">
                                                                {
                                                                    usage.consumable_name
                                                                }
                                                            </p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {usage.quantity}{' '}
                                                                {usage.unit_label ??
                                                                    ''}{' '}
                                                                |{' '}
                                                                {formatMoney(
                                                                    usage.line_cost,
                                                                )}
                                                            </p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {actorName(
                                                                    usage.recordedBy,
                                                                )}{' '}
                                                                |{' '}
                                                                {formatDateTime(
                                                                    usage.used_at,
                                                                )}
                                                            </p>
                                                            {usage.notes ? (
                                                                <p className="text-sm">
                                                                    {
                                                                        usage.notes
                                                                    }
                                                                </p>
                                                            ) : null}
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() =>
                                                                router.delete(
                                                                    `/laboratory/request-items/${labRequestItem.id}/consumables/${usage.id}`,
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            Remove
                                                        </Button>
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                        No consumables recorded yet.
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Request Item Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3 text-sm">
                                <SummaryRow
                                    label="Test"
                                    value={
                                        labRequestItem.test?.test_name ??
                                        'Lab test'
                                    }
                                />
                                <SummaryRow
                                    label="Result Type"
                                    value={
                                        labRequestItem.test?.result_type_name ??
                                        labelize(
                                            labRequestItem.test
                                                ?.result_capture_type,
                                        )
                                    }
                                />
                                <SummaryRow
                                    label="Ordered At"
                                    value={formatDateTime(
                                        labRequestItem.request?.request_date,
                                    )}
                                />
                                <SummaryRow
                                    label="Ordered By"
                                    value={actorName(
                                        labRequestItem.request?.requestedBy,
                                    )}
                                />
                                <SummaryRow
                                    label="Status"
                                    value={labelize(labRequestItem.status)}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Patient and Visit</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3 text-sm">
                                <SummaryRow
                                    label="Patient"
                                    value={
                                        patient
                                            ? `${patient.first_name} ${patient.last_name}`
                                            : 'Unknown patient'
                                    }
                                />
                                <SummaryRow
                                    label="MRN"
                                    value={patient?.patient_number ?? 'N/A'}
                                />
                                <SummaryRow
                                    label="Visit Number"
                                    value={
                                        labRequestItem.request?.visit
                                            ?.visit_number ?? 'N/A'
                                    }
                                />
                                <SummaryRow
                                    label="Phone"
                                    value={patient?.phone_number ?? 'N/A'}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Cost Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3 text-sm">
                                <SummaryRow
                                    label="Billed Price"
                                    value={formatMoney(labRequestItem.price)}
                                />
                                <SummaryRow
                                    label="Actual Cost"
                                    value={formatMoney(
                                        labRequestItem.actual_cost,
                                    )}
                                />
                                <SummaryRow
                                    label="Variance"
                                    value={formatMoney(variance)}
                                />
                                <SummaryRow
                                    label="Costed At"
                                    value={formatDateTime(
                                        labRequestItem.costed_at,
                                    )}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Clinical Notes</CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm">
                                {labRequestItem.request?.clinical_notes ?? (
                                    <p className="text-muted-foreground">
                                        No clinical notes were attached to this
                                        request.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
