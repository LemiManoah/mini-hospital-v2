import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import {
    type LaboratoryQueueRequest,
    type LaboratoryQueuePageProps,
    type LaboratoryRequestItem,
    type LaboratoryRequestSummary,
    type LaboratoryResultEntry,
    type LaboratoryResultValue,
} from '@/types/laboratory';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const labelize = (value: string | null | undefined): string =>
    value
        ? value
              .replaceAll('_', ' ')
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : 'Not set';

const formatDateTime = (value: string | null | undefined): string =>
    value ? new Date(value).toLocaleString() : 'Not yet recorded';

const actorName = (
    actor?: { first_name: string; last_name: string } | null,
): string =>
    actor ? `${actor.first_name} ${actor.last_name}` : 'Not recorded';

const actorFromResultEntry = (
    resultEntry: LaboratoryResultEntry | null,
    field: 'enteredBy' | 'reviewedBy' | 'approvedBy',
    legacyField: 'entered_by' | 'reviewed_by' | 'approved_by',
): string =>
    actorName(resultEntry?.[field] ?? resultEntry?.[legacyField] ?? null);

const formatPatientAge = (
    patient?: {
        age?: number | null;
        age_units?: string | null;
    } | null,
): string => {
    if (patient?.age === null || patient?.age === undefined) {
        return 'N/A';
    }

    const units = patient.age_units
        ? patient.age_units.replaceAll('_', ' ')
        : 'years';

    return `${patient.age} ${units}`;
};

const workflowVariant = (
    workflowStage: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (workflowStage === 'approved') return 'default';
    if (workflowStage === 'reviewed' || workflowStage === 'result_entered') {
        return 'secondary';
    }
    if (workflowStage === 'cancelled' || workflowStage === 'rejected') {
        return 'destructive';
    }

    return 'outline';
};

const priorityVariant = (
    priority: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (priority === 'critical' || priority === 'stat') return 'destructive';
    if (priority === 'urgent') return 'secondary';

    return 'outline';
};

const resultValueDisplay = (value: LaboratoryResultValue): string =>
    value.display_value ?? value.value_text ?? `${value.value_numeric ?? ''}`;

type ModalMode = 'collect' | 'enter' | 'review' | 'view';

type ActiveModal = {
    mode: ModalMode;
    item: LaboratoryRequestItem;
    request: LaboratoryRequestSummary | null;
} | null;

type QueueCardRequest = LaboratoryQueueRequest & {
    request_count: number;
};

const priorityWeight = (priority: string): number =>
    ({
        critical: 0,
        stat: 1,
        urgent: 2,
        routine: 3,
    })[priority] ?? 4;

const toRequestSummary = (
    request: LaboratoryQueueRequest,
): LaboratoryRequestSummary => ({
    id: request.id,
    request_date: request.request_date,
    priority: request.priority,
    status: request.status,
    clinical_notes: request.clinical_notes,
    requestedBy: request.requestedBy,
    visit: request.visit,
});

const withRequestSummary = (
    request: LaboratoryQueueRequest,
): QueueCardRequest => {
    const summary = toRequestSummary(request);

    return {
        ...request,
        request_count: 1,
        items: request.items.map((item) => ({
            ...item,
            request: item.request ?? summary,
        })),
    };
};

const groupIncomingRequests = (
    requests: LaboratoryQueueRequest[],
): QueueCardRequest[] => {
    const groupedRequests = new Map<string, QueueCardRequest>();

    requests.forEach((request) => {
        const visitId = request.visit?.id ?? `request:${request.id}`;
        const patientId = request.visit?.patient?.id ?? 'unknown-patient';
        const groupKey = `${visitId}:${patientId}`;
        const normalizedRequest = withRequestSummary(request);
        const existing = groupedRequests.get(groupKey);

        if (!existing) {
            groupedRequests.set(groupKey, {
                ...normalizedRequest,
                id: groupKey,
            });
            return;
        }

        groupedRequests.set(groupKey, {
            ...existing,
            request_date:
                new Date(normalizedRequest.request_date).getTime() >
                new Date(existing.request_date).getTime()
                    ? normalizedRequest.request_date
                    : existing.request_date,
            priority:
                priorityWeight(normalizedRequest.priority) <
                priorityWeight(existing.priority)
                    ? normalizedRequest.priority
                    : existing.priority,
            items: [...existing.items, ...normalizedRequest.items].sort(
                (left, right) =>
                    new Date(
                        right.request?.request_date ?? normalizedRequest.request_date,
                    ).getTime() -
                    new Date(
                        left.request?.request_date ?? normalizedRequest.request_date,
                    ).getTime(),
            ),
            request_count: existing.request_count + 1,
        });
    });

    return [...groupedRequests.values()].sort(
        (left, right) =>
            new Date(right.request_date).getTime() -
            new Date(left.request_date).getTime(),
    );
};

export default function LaboratoryQueuePage({
    page,
    requests,
    filters,
}: LaboratoryQueuePageProps) {
    const inertiaPage = usePage<SharedData>();
    const [search, setSearch] = useState(filters.search ?? '');
    const [activeModal, setActiveModal] = useState<ActiveModal>(null);

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                page.route,
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['requests', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [filters.search, page.route, search]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Laboratory', href: '/laboratory/dashboard' },
        { title: page.title, href: page.route },
    ];
    const queueRequests = useMemo(
        () =>
            page.stage === 'incoming'
                ? groupIncomingRequests(requests.data)
                : requests.data.map(withRequestSummary),
        [page.stage, requests.data],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={page.title} />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold">{page.title}</h1>
                        {page.stage !== 'view_results' ? (
                            <p className="text-sm text-muted-foreground">
                                {page.description}
                            </p>
                        ) : null}
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        {page.stage !== 'view_results' ? (
                            <Button variant="outline" asChild>
                                <Link href="/laboratory/dashboard">
                                    Dashboard
                                </Link>
                            </Button>
                        ) : null}
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search patient, visit, or test..."
                        />
                    </div>
                </div>

                <div className="flex flex-col gap-4">
                    {queueRequests.length === 0 ? (
                        <Card>
                            <CardContent className="py-12 text-center text-sm text-muted-foreground">
                                No patients matched this queue.
                            </CardContent>
                        </Card>
                    ) : (
                        queueRequests.map((request) => {
                            const patient = request.visit?.patient;

                            return (
                                <Card
                                    key={request.id}
                                    className="overflow-hidden border-border/60 shadow-none"
                                >
                                    <CardHeader className="gap-2 border-b bg-muted/5 px-4 py-3">
                                        <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                            <div className="flex flex-col gap-1">
                                                <CardTitle className="text-base">
                                                    {patient
                                                        ? `${patient.first_name} ${patient.last_name}`
                                                        : 'Unknown patient'}
                                                </CardTitle>
                                                <p className="text-xs leading-4 text-muted-foreground">
                                                    Visit{' '}
                                                    {request.visit
                                                        ?.visit_number ??
                                                        'N/A'}{' '}
                                                    | MRN{' '}
                                                    {patient?.patient_number ??
                                                        'N/A'}{' '}
                                                    | Gender{' '}
                                                    {patient?.gender
                                                        ? labelize(
                                                              patient.gender,
                                                          )
                                                        : 'N/A'}{' '}
                                                    | Age{' '}
                                                    {formatPatientAge(patient)}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-1.5 lg:max-w-xs lg:justify-end">
                                                <Badge
                                                    variant={priorityVariant(
                                                        request.priority,
                                                    )}
                                                    className="px-2 py-0.5 text-[11px]"
                                                >
                                                    {labelize(request.priority)}
                                                </Badge>
                                                {page.stage === 'incoming' ? (
                                                    <>
                                                        <Badge
                                                            variant="outline"
                                                            className="px-2 py-0.5 text-[11px]"
                                                        >
                                                            {request.items.length}{' '}
                                                            {request.items.length ===
                                                            1
                                                                ? 'test'
                                                                : 'tests'}
                                                        </Badge>
                                                        {request.request_count >
                                                        1 ? (
                                                            <Badge
                                                                variant="outline"
                                                                className="px-2 py-0.5 text-[11px]"
                                                            >
                                                                {
                                                                    request.request_count
                                                                }{' '}
                                                                batches
                                                            </Badge>
                                                        ) : null}
                                                    </>
                                                ) : (
                                                    <Badge
                                                        variant="outline"
                                                        className="px-2 py-0.5 text-[11px]"
                                                    >
                                                        {labelize(
                                                            request.status,
                                                        )}
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="px-0 py-0">
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead className="px-3 py-2 text-xs">
                                                            Order
                                                        </TableHead>
                                                        <TableHead className="py-2 text-xs">
                                                            Specimen
                                                        </TableHead>
                                                        <TableHead className="py-2 text-xs">
                                                            Workflow
                                                        </TableHead>
                                                        <TableHead className="py-2 text-xs">
                                                            Timeline
                                                        </TableHead>
                                                        <TableHead className="py-2 text-right text-xs">
                                                            Action
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {request.items.map(
                                                        (item) => (
                                                            <TableRow
                                                                key={item.id}
                                                                className="align-top"
                                                            >
                                                                <TableCell className="px-3 py-2 align-top">
                                                                    <div className="flex max-w-sm flex-col gap-1.5 whitespace-normal">
                                                                        <p className="text-sm font-medium">
                                                                            {item
                                                                                .test
                                                                                ?.test_name ??
                                                                                'Lab test'}
                                                                        </p>
                                                                        <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                                                            <span className="rounded-full bg-muted px-2 py-0.5">
                                                                                {item
                                                                                    .test
                                                                                    ?.category ??
                                                                                    'Uncategorized'}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell className="px-3 py-2 align-top">
                                                                    <div className="flex min-w-32 flex-col gap-1.5 whitespace-normal">
                                                                        <span className="text-sm">
                                                                            {item
                                                                                .specimen
                                                                                ?.specimen_type_name ??
                                                                                item
                                                                                    .test
                                                                                    ?.specimen_type ??
                                                                                'Not yet picked'}
                                                                        </span>
                                                                        {item
                                                                            .specimen
                                                                            ?.outside_sample ? (
                                                                            <span className="w-fit rounded-full bg-amber-50 px-2 py-0.5 text-[11px] text-amber-700">
                                                                                Outside
                                                                                sample
                                                                            </span>
                                                                        ) : null}
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell className="px-3 py-2 align-top">
                                                                    <div className="flex flex-col gap-1.5">
                                                                        <Badge
                                                                            variant={workflowVariant(
                                                                                item.workflow_stage,
                                                                            )}
                                                                            className="w-fit px-2 py-0.5 text-[11px]"
                                                                        >
                                                                            {labelize(
                                                                                item.workflow_stage,
                                                                            )}
                                                                        </Badge>
                                                                        <span className="text-[11px] text-muted-foreground">
                                                                            {labelize(
                                                                                item.status,
                                                                            )}
                                                                        </span>
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell className="px-3 py-2 align-top">
                                                                    <div className="grid gap-1.5 text-xs whitespace-normal text-muted-foreground">
                                                                        <div className="rounded-md bg-muted/20 px-2 py-1.5">
                                                                            <span className="block text-[11px] font-medium tracking-wide text-foreground/70 uppercase">
                                                                                Requested
                                                                            </span>
                                                                            <span>
                                                                                {formatDateTime(
                                                                                    item
                                                                                        .request
                                                                                        ?.request_date ??
                                                                                        request.request_date,
                                                                                )}
                                                                            </span>
                                                                        </div>
                                                                        <div className="rounded-md bg-muted/20 px-2 py-1.5">
                                                                            <span className="block text-[11px] font-medium tracking-wide text-foreground/70 uppercase">
                                                                                Sample
                                                                            </span>
                                                                            <span>
                                                                                {formatDateTime(
                                                                                    item
                                                                                        .specimen
                                                                                        ?.collected_at ??
                                                                                        item.received_at,
                                                                                )}
                                                                            </span>
                                                                        </div>
                                                                        <div className="rounded-md bg-muted/20 px-2 py-1.5">
                                                                            <span className="block text-[11px] font-medium tracking-wide text-foreground/70 uppercase">
                                                                                Result
                                                                            </span>
                                                                            <span>
                                                                                {formatDateTime(
                                                                                    item.result_entered_at,
                                                                                )}
                                                                            </span>
                                                                        </div>
                                                                        <div className="rounded-md bg-muted/20 px-2 py-1.5">
                                                                            <span className="block text-[11px] font-medium tracking-wide text-foreground/70 uppercase">
                                                                                Release
                                                                            </span>
                                                                            <span>
                                                                                {formatDateTime(
                                                                                    item.approved_at,
                                                                                )}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell className="px-3 py-2 text-right align-top">
                                                                    <div className="flex flex-col items-end gap-2 sm:flex-row sm:justify-end">
                                                                        <Button
                                                                            type="button"
                                                                            variant="outline"
                                                                            size="sm"
                                                                            onClick={() =>
                                                                                setActiveModal(
                                                                                    {
                                                                                        mode: modalModeForStage(
                                                                                            page.stage,
                                                                                        ),
                                                                                        item,
                                                                                        request: item.request ??
                                                                                            request,
                                                                                    },
                                                                                )
                                                                            }
                                                                        >
                                                                            {
                                                                                page.action_label
                                                                            }
                                                                        </Button>
                                                                        {page.stage ===
                                                                            'view_results' &&
                                                                        item.result_visible ? (
                                                                            <Button
                                                                                type="button"
                                                                                size="sm"
                                                                                asChild
                                                                            >
                                                                                <a
                                                                                    href={`/laboratory/request-items/${item.id}/print`}
                                                                                    target="_blank"
                                                                                    rel="noreferrer"
                                                                                >
                                                                                    Print
                                                                                </a>
                                                                            </Button>
                                                                        ) : null}
                                                                    </div>
                                                                </TableCell>
                                                            </TableRow>
                                                        ),
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })
                    )}
                </div>

                {(requests.prev_page_url ?? requests.next_page_url) ? (
                    <div className="flex items-center justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            asChild={Boolean(requests.prev_page_url)}
                            disabled={!requests.prev_page_url}
                        >
                            {requests.prev_page_url ? (
                                <Link href={requests.prev_page_url}>
                                    Previous
                                </Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            asChild={Boolean(requests.next_page_url)}
                            disabled={!requests.next_page_url}
                        >
                            {requests.next_page_url ? (
                                <Link href={requests.next_page_url}>Next</Link>
                            ) : (
                                <span>Next</span>
                            )}
                        </Button>
                    </div>
                ) : null}
            </div>

            <QueueModal
                activeModal={activeModal}
                onOpenChange={(open) => !open && setActiveModal(null)}
                redirectTo={inertiaPage.url}
            />
        </AppLayout>
    );
}

function modalModeForStage(
    stage: LaboratoryQueuePageProps['page']['stage'],
): ModalMode {
    if (stage === 'incoming') return 'collect';
    if (stage === 'enter_results') return 'enter';
    if (stage === 'review_results') return 'review';

    return 'view';
}

function QueueModal({
    activeModal,
    onOpenChange,
    redirectTo,
}: {
    activeModal: ActiveModal;
    onOpenChange: (open: boolean) => void;
    redirectTo: string;
}) {
    if (activeModal === null) {
        return null;
    }

    return activeModal.mode === 'collect' ? (
        <CollectSampleDialog
            item={activeModal.item}
            open
            onOpenChange={onOpenChange}
            redirectTo={redirectTo}
        />
    ) : activeModal.mode === 'enter' ? (
        <EnterResultDialog
            item={activeModal.item}
            open
            onOpenChange={onOpenChange}
            redirectTo={redirectTo}
        />
    ) : activeModal.mode === 'review' ? (
        <ReviewResultDialog
            item={activeModal.item}
            open
            onOpenChange={onOpenChange}
            redirectTo={redirectTo}
        />
    ) : (
        <ViewResultDialog
            item={activeModal.item}
            request={activeModal.request}
            open
            onOpenChange={onOpenChange}
        />
    );
}

function CollectSampleDialog({
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
                                    <SelectItem
                                        key={option.id}
                                        value={option.id}
                                    >
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

function EnterResultDialog({
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
                        form.post(
                            `/laboratory/request-items/${item.id}/results`,
                            {
                                preserveScroll: true,
                                onSuccess: () => onOpenChange(false),
                            },
                        );
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

function ReviewResultDialog({
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
    const values = resultEntry?.values ?? [];
    const form = useForm({
        review_notes: resultEntry?.review_notes ?? '',
        approval_notes: resultEntry?.approval_notes ?? '',
        redirect_to: redirectTo,
    });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Review and Release Results</DialogTitle>
                    <DialogDescription>
                        Confirm the entered result and release it in one step.
                    </DialogDescription>
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
                    <div className="grid gap-2">
                        <Label htmlFor="approval_notes">Release Notes</Label>
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
                            form.post(
                                `/laboratory/request-items/${item.id}/approve`,
                                {
                                    preserveScroll: true,
                                    onSuccess: () => onOpenChange(false),
                                },
                            )
                        }
                    >
                        Review and Release
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ViewResultDialog({
    item,
    request,
    open,
    onOpenChange,
}: {
    item: LaboratoryRequestItem;
    request: LaboratoryRequestSummary | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const resultEntry = item.resultEntry ?? item.result_entry ?? null;
    const values = resultEntry?.values ?? [];
    const patient = request?.visit?.patient ?? item.request?.visit?.patient;
    const visit = request?.visit ?? item.request?.visit;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Full Result View</DialogTitle>
                    <DialogDescription>
                        {item.test?.test_name ?? 'Lab test'} for accession{' '}
                        {item.specimen?.accession_number ?? 'not assigned'}.
                    </DialogDescription>
                </DialogHeader>

                <div className="rounded-xl border bg-muted/20">
                    <div className="border-b px-5 py-4">
                        <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-1">
                                <p className="text-base font-semibold">
                                    {patient
                                        ? `${patient.first_name} ${patient.last_name}`
                                        : 'Unknown patient'}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    MRN {patient?.patient_number ?? 'N/A'} |
                                    Visit {visit?.visit_number ?? 'N/A'}
                                </p>
                            </div>
                            <Badge variant="default">
                                {labelize(item.workflow_stage)}
                            </Badge>
                        </div>
                    </div>

                    <div className="grid gap-x-6 gap-y-4 px-5 py-4 md:grid-cols-2">
                        <ResultMetaRow
                            label="Test"
                            value={item.test?.test_name ?? 'Lab test'}
                        />
                        <ResultMetaRow
                            label="Accession"
                            value={
                                item.specimen?.accession_number ??
                                'Not assigned'
                            }
                        />
                        <ResultMetaRow
                            label="Specimen"
                            value={
                                item.specimen?.specimen_type_name ??
                                item.test?.specimen_type ??
                                'Not recorded'
                            }
                        />
                        <ResultMetaRow
                            label="Collected At"
                            value={formatDateTime(item.specimen?.collected_at)}
                        />
                        <ResultMetaRow
                            label="Entered By"
                            value={actorFromResultEntry(
                                resultEntry,
                                'enteredBy',
                                'entered_by',
                            )}
                        />
                        <ResultMetaRow
                            label="Reviewed By"
                            value={actorFromResultEntry(
                                resultEntry,
                                'reviewedBy',
                                'reviewed_by',
                            )}
                        />
                        <ResultMetaRow
                            label="Approved By"
                            value={actorFromResultEntry(
                                resultEntry,
                                'approvedBy',
                                'approved_by',
                            )}
                        />
                        <ResultMetaRow
                            label="Released At"
                            value={formatDateTime(resultEntry?.released_at)}
                        />
                    </div>
                </div>

                <div className="rounded-xl border">
                    <div className="border-b px-5 py-3">
                        <p className="font-medium">Reported Values</p>
                    </div>
                    <div className="px-5 py-3">
                        {values.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No released values were found.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Parameter</TableHead>
                                        <TableHead>Result</TableHead>
                                        <TableHead>Unit</TableHead>
                                        <TableHead>Reference</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {values.map((value) => (
                                        <TableRow key={value.id}>
                                            <TableCell className="font-medium">
                                                {value.label}
                                            </TableCell>
                                            <TableCell>
                                                {resultValueDisplay(value)}
                                            </TableCell>
                                            <TableCell>
                                                {value.unit ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {value.reference_range ?? '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </div>
                </div>

                {resultEntry?.result_notes ? (
                    <ResultNoteSection
                        label="Bench Notes"
                        value={resultEntry.result_notes}
                    />
                ) : null}
                {resultEntry?.review_notes ? (
                    <ResultNoteSection
                        label="Review Notes"
                        value={resultEntry.review_notes}
                    />
                ) : null}
                {resultEntry?.approval_notes ? (
                    <ResultNoteSection
                        label="Release Notes"
                        value={resultEntry.approval_notes}
                    />
                ) : null}

                <DialogFooter className="justify-between sm:justify-between">
                    <Button type="button" asChild>
                        <a
                            href={`/laboratory/request-items/${item.id}/print`}
                            target="_blank"
                            rel="noreferrer"
                        >
                            Print PDF
                        </a>
                    </Button>
                    <Button type="button" onClick={() => onOpenChange(false)}>
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ResultMetaRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-1">
            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p className="text-sm font-medium">{value}</p>
        </div>
    );
}

function ResultNoteSection({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border px-5 py-4 text-sm">
            <p className="font-medium">{label}</p>
            <p className="mt-2 leading-6 text-muted-foreground">{value}</p>
        </div>
    );
}
