import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    actorFromResultEntry,
    formatDateTime,
    labelize,
    resultValueDisplay,
} from './queue-utils';

type ResultActor = { first_name: string; last_name: string } | null | undefined;

type ResultValue = {
    id: string;
    label: string;
    display_value?: string | null;
    value_text: string | null;
    value_numeric: number | null;
    unit: string | null;
    reference_range: string | null;
};

type ResultEntry = {
    result_notes?: string | null;
    review_notes?: string | null;
    approval_notes?: string | null;
    correction_reason?: string | null;
    released_at?: string | null;
    enteredBy?: ResultActor;
    reviewedBy?: ResultActor;
    approvedBy?: ResultActor;
    correctedBy?: ResultActor;
    entered_by?: ResultActor;
    reviewed_by?: ResultActor;
    approved_by?: ResultActor;
    corrected_by?: ResultActor;
    values?: ResultValue[] | null;
};

type ResultPatient = {
    first_name?: string | null;
    last_name?: string | null;
    patient_number?: string | null;
} | null;

type ResultVisit = {
    visit_number?: string | null;
    patient?: ResultPatient;
} | null;

type ResultRequest = {
    visit?: ResultVisit;
    request_date?: string | null;
    priority?: string | null;
    status?: string | null;
    clinical_notes?: string | null;
} | null;

type ResultItem = {
    id: string;
    workflow_stage?: string;
    specimen?: {
        accession_number?: string | null;
        specimen_type_name?: string | null;
        collected_at?: string | null;
    } | null;
    test?: {
        test_name?: string | null;
        specimen_type?: string | null;
    } | null;
    request?: ResultRequest;
    resultEntry?: ResultEntry | null;
    result_entry?: ResultEntry | null;
} | null;

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

export function LabResultDialog({
    item,
    request,
    open,
    onOpenChange,
}: {
    item: ResultItem;
    request: ResultRequest;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const resultEntry = item?.resultEntry ?? item?.result_entry ?? null;
    const values = resultEntry?.values ?? [];
    const patient = request?.visit?.patient ?? item?.request?.visit?.patient;
    const visit = request?.visit ?? item?.request?.visit;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Full Result View</DialogTitle>
                    <DialogDescription>
                        {item?.test?.test_name ?? 'Lab test'} for accession{' '}
                        {item?.specimen?.accession_number ?? 'not assigned'}.
                    </DialogDescription>
                </DialogHeader>

                <div className="rounded-xl border bg-muted/20">
                    <div className="border-b px-5 py-4">
                        <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-1">
                                <p className="text-base font-semibold">
                                    {patient
                                        ? `${patient.first_name ?? ''} ${patient.last_name ?? ''}`.trim() ||
                                          'Unknown patient'
                                        : 'Unknown patient'}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    MRN {patient?.patient_number ?? 'N/A'} |
                                    Visit {visit?.visit_number ?? 'N/A'}
                                </p>
                            </div>
                            <Badge variant="default">
                                {labelize(item?.workflow_stage)}
                            </Badge>
                        </div>
                    </div>

                    <div className="grid gap-x-6 gap-y-4 px-5 py-4 md:grid-cols-2">
                        <ResultMetaRow
                            label="Test"
                            value={item?.test?.test_name ?? 'Lab test'}
                        />
                        <ResultMetaRow
                            label="Accession"
                            value={
                                item?.specimen?.accession_number ??
                                'Not assigned'
                            }
                        />
                        <ResultMetaRow
                            label="Specimen"
                            value={
                                item?.specimen?.specimen_type_name ??
                                item?.test?.specimen_type ??
                                'Not recorded'
                            }
                        />
                        <ResultMetaRow
                            label="Collected At"
                            value={formatDateTime(item?.specimen?.collected_at)}
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
                {resultEntry?.correction_reason ? (
                    <ResultNoteSection
                        label="Correction Reason"
                        value={resultEntry.correction_reason}
                    />
                ) : null}

                <DialogFooter className="justify-between sm:justify-between">
                    <Button type="button" asChild>
                        <a
                            href={
                                item
                                    ? `/laboratory/request-items/${item.id}/print`
                                    : '#'
                            }
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
