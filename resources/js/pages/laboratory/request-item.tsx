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
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

type ResultParameterDraft = {
    lab_test_result_parameter_id: string;
    value: string;
};

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

const actorFromResultEntry = (
    resultEntry: LaboratoryResultEntry | null,
    field: 'enteredBy' | 'reviewedBy' | 'approvedBy' | 'correctedBy',
    legacyField: 'entered_by' | 'reviewed_by' | 'approved_by' | 'corrected_by',
): string =>
    actorName(resultEntry?.[field] ?? resultEntry?.[legacyField] ?? null);

const workflowVariant = (
    workflowStage: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (workflowStage === 'approved') return 'default';
    if (workflowStage === 'reviewed') return 'secondary';
    if (workflowStage === 'cancelled' || workflowStage === 'rejected') {
        return 'destructive';
    }

    return 'outline';
};

const resultValueDisplay = (value: LaboratoryResultValue): string =>
    value.display_value ?? value.value_text ?? `${value.value_numeric ?? ''}`;

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
    const isApproved = labRequestItem.workflow_stage === 'approved';
    const canRelease =
        labRequestItem.workflow_stage === 'result_entered' ||
        labRequestItem.workflow_stage === 'reviewed' ||
        labRequestItem.workflow_stage === 'approved';
    const [correctionMode, setCorrectionMode] = useState(false);
    const resultEditingLocked = isApproved && !correctionMode;
    const pageTitle = 'Result Correction';
    const resultManagementTitle =
        isApproved || correctionMode ? 'Result Correction' : 'Result Entry';
    const resultManagementDescription =
        isApproved || correctionMode
            ? 'Correct a released result when needed. Saving a correction hides it from clinicians until it is reviewed and released again.'
            : 'Save the bench result in the configured format for this test.';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Laboratory', href: '/laboratory/dashboard' },
        {
            title: 'View Results',
            href: '/laboratory/view-results',
        },
        {
            title: pageTitle,
            href: `/laboratory/request-items/${labRequestItem.id}`,
        },
    ];

    const resultForm = useForm({
        result_notes: resultEntry?.result_notes ?? '',
        free_entry_value:
            resultValues[0]?.value_text ?? resultValues[0]?.display_value ?? '',
        selected_option_label:
            resultValues[0]?.value_text ?? resultValues[0]?.display_value ?? '',
        correction_reason: '',
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

    const releaseForm = useForm({
        review_notes: resultEntry?.review_notes ?? '',
        approval_notes: resultEntry?.approval_notes ?? '',
    });
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`${pageTitle} ${labRequestItem.test?.test_name ?? ''}`}
            />
            <div className="m-4 flex flex-col gap-6">
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div className="flex flex-col gap-2">
                                <p className="text-sm font-medium text-muted-foreground">
                                    {pageTitle}
                                </p>
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
                                <p className="text-sm text-muted-foreground">
                                    {patient
                                        ? `${patient.first_name} ${patient.last_name}`
                                        : 'Unknown patient'}
                                </p>
                                <CardDescription>
                                    Review the released result and only reopen
                                    it when a correction is necessary.
                                </CardDescription>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" asChild>
                                    <Link href="/laboratory/view-results">
                                        Back to View Results
                                    </Link>
                                </Button>
                                {labRequestItem.result_visible ? (
                                    <Button type="button" asChild>
                                        <a
                                            href={`/laboratory/request-items/${labRequestItem.id}/print`}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Download PDF Result
                                        </a>
                                    </Button>
                                ) : null}
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/laboratory/request-items/${labRequestItem.id}/consumables`}
                                    >
                                        Consumables
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.45fr_0.95fr]">
                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>{resultManagementTitle}</CardTitle>
                                <CardDescription>
                                    {resultManagementDescription}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        resultForm.post(
                                            correctionMode
                                                ? `/laboratory/request-items/${labRequestItem.id}/correct`
                                                : `/laboratory/request-items/${labRequestItem.id}/results`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className="flex flex-col gap-4"
                                >
                                    {isApproved && !correctionMode ? (
                                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                            This result has already been
                                            released. Start a correction to
                                            reopen it, update the values, and
                                            send it back through review and
                                            release.
                                        </div>
                                    ) : null}

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
                                                disabled={resultEditingLocked}
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
                                                disabled={resultEditingLocked}
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
                                                                resultEditingLocked
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
                                            disabled={resultEditingLocked}
                                        />
                                        <InputError
                                            message={
                                                resultForm.errors.result_notes
                                            }
                                        />
                                    </div>

                                    {correctionMode ? (
                                        <div className="grid gap-2">
                                            <Label htmlFor="correction_reason">
                                                Correction Reason
                                            </Label>
                                            <Textarea
                                                id="correction_reason"
                                                rows={3}
                                                value={
                                                    resultForm.data
                                                        .correction_reason
                                                }
                                                onChange={(event) =>
                                                    resultForm.setData(
                                                        'correction_reason',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Explain what was wrong with the released result and why it is being corrected."
                                            />
                                            <InputError
                                                message={
                                                    resultForm.errors
                                                        .correction_reason
                                                }
                                            />
                                        </div>
                                    ) : null}

                                    <div className="flex justify-end">
                                        <div className="flex flex-wrap justify-end gap-2">
                                            {isApproved ? (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => {
                                                        setCorrectionMode(
                                                            (current) =>
                                                                !current,
                                                        );

                                                        if (correctionMode) {
                                                            resultForm.setData(
                                                                'correction_reason',
                                                                '',
                                                            );
                                                        }
                                                    }}
                                                    disabled={
                                                        resultForm.processing
                                                    }
                                                >
                                                    {correctionMode
                                                        ? 'Cancel Correction'
                                                        : 'Start Result Correction'}
                                                </Button>
                                            ) : null}
                                            <Button
                                                type="submit"
                                                disabled={
                                                    resultEditingLocked ||
                                                    resultForm.processing
                                                }
                                            >
                                                {correctionMode
                                                    ? 'Save Corrected Result'
                                                    : 'Save Results'}
                                            </Button>
                                        </div>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Review and Release</CardTitle>
                                <CardDescription>
                                    Review and release the result in one step.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-6">
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        releaseForm.post(
                                            `/laboratory/request-items/${labRequestItem.id}/approve`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className="flex flex-col gap-4 rounded-lg border p-4"
                                >
                                    <div className="grid gap-2 text-sm text-muted-foreground md:grid-cols-2">
                                        <p>
                                            Reviewed by:{' '}
                                            {actorFromResultEntry(
                                                resultEntry,
                                                'reviewedBy',
                                                'reviewed_by',
                                            )}{' '}
                                            |{' '}
                                            {formatDateTime(
                                                resultEntry?.reviewed_at,
                                            )}
                                        </p>
                                        <p>
                                            Approved by:{' '}
                                            {actorFromResultEntry(
                                                resultEntry,
                                                'approvedBy',
                                                'approved_by',
                                            )}{' '}
                                            |{' '}
                                            {formatDateTime(
                                                resultEntry?.approved_at,
                                            )}
                                        </p>
                                    </div>
                                    {resultEntry?.corrected_at ? (
                                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                            <p className="font-medium">
                                                Last correction
                                            </p>
                                            <p className="mt-1">
                                                {actorFromResultEntry(
                                                    resultEntry,
                                                    'correctedBy',
                                                    'corrected_by',
                                                )}{' '}
                                                on{' '}
                                                {formatDateTime(
                                                    resultEntry.corrected_at,
                                                )}
                                            </p>
                                            {resultEntry.correction_reason ? (
                                                <p className="mt-2">
                                                    {
                                                        resultEntry.correction_reason
                                                    }
                                                </p>
                                            ) : null}
                                        </div>
                                    ) : null}
                                    <div className="grid gap-2">
                                        <Label htmlFor="review_notes">
                                            Review Notes
                                        </Label>
                                        <Textarea
                                            id="review_notes"
                                            rows={3}
                                            value={
                                                releaseForm.data.review_notes
                                            }
                                            onChange={(event) =>
                                                releaseForm.setData(
                                                    'review_notes',
                                                    event.target.value,
                                                )
                                            }
                                            disabled={!canRelease || isApproved}
                                            placeholder="Optional review notes"
                                        />
                                        <InputError
                                            message={
                                                releaseForm.errors.review_notes
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="approval_notes">
                                            Release Notes
                                        </Label>
                                        <Textarea
                                            id="approval_notes"
                                            rows={3}
                                            value={
                                                releaseForm.data.approval_notes
                                            }
                                            onChange={(event) =>
                                                releaseForm.setData(
                                                    'approval_notes',
                                                    event.target.value,
                                                )
                                            }
                                            disabled={!canRelease}
                                            placeholder="Optional release notes"
                                        />
                                        <InputError
                                            message={
                                                releaseForm.errors
                                                    .approval_notes
                                            }
                                        />
                                    </div>
                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            disabled={
                                                !canRelease ||
                                                releaseForm.processing
                                            }
                                        >
                                            {isApproved
                                                ? 'Update Release Notes'
                                                : 'Review and Release'}
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
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
