import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    type LaboratoryQueuePageProps,
    type LaboratoryRequestItem,
} from '@/types/laboratory';
import {
    formatDateTime,
    formatPatientAge,
    labelize,
    priorityVariant,
    type QueueCardRequest,
    workflowVariant,
} from './queue-utils';

export function QueuePatientCard({
    pageStage,
    actionLabel,
    request,
    onAction,
}: {
    pageStage: LaboratoryQueuePageProps['page']['stage'];
    actionLabel: string;
    request: QueueCardRequest;
    onAction: (item: LaboratoryRequestItem, request: QueueCardRequest) => void;
}) {
    const patient = request.visit?.patient;

    return (
        <Card className="overflow-hidden border-border/60 shadow-none">
            <CardHeader className="gap-2 border-b bg-muted/5 px-4 py-3">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div className="flex flex-col gap-1">
                        <CardTitle className="text-base">
                            {patient
                                ? `${patient.first_name} ${patient.last_name}`
                                : 'Unknown patient'}
                        </CardTitle>
                        <p className="text-xs leading-4 text-muted-foreground">
                            Visit {request.visit?.visit_number ?? 'N/A'} | MRN{' '}
                            {patient?.patient_number ?? 'N/A'} | Gender{' '}
                            {patient?.gender ? labelize(patient.gender) : 'N/A'}{' '}
                            | Age {formatPatientAge(patient)}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-1.5 lg:max-w-xs lg:justify-end">
                        <Badge
                            variant={priorityVariant(request.priority)}
                            className="px-2 py-0.5 text-[11px]"
                        >
                            {labelize(request.priority)}
                        </Badge>
                        {pageStage === 'incoming' ? (
                            <>
                                <Badge
                                    variant="outline"
                                    className="px-2 py-0.5 text-[11px]"
                                >
                                    {request.items.length}{' '}
                                    {request.items.length === 1
                                        ? 'test'
                                        : 'tests'}
                                </Badge>
                                {request.request_count > 1 ? (
                                    <Badge
                                        variant="outline"
                                        className="px-2 py-0.5 text-[11px]"
                                    >
                                        {request.request_count} batches
                                    </Badge>
                                ) : null}
                            </>
                        ) : (
                            <Badge
                                variant="outline"
                                className="px-2 py-0.5 text-[11px]"
                            >
                                {labelize(request.status)}
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
                                    Stage
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
                            {request.items.map((item) => (
                                <TableRow key={item.id} className="align-top">
                                    <TableCell className="px-3 py-2 align-top">
                                        <div className="flex max-w-sm flex-col gap-1.5 whitespace-normal">
                                            <p className="text-sm font-medium">
                                                {item.test?.test_name ??
                                                    'Lab test'}
                                            </p>
                                            <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                                <span className="rounded-full bg-muted px-2 py-0.5">
                                                    {item.test?.category ??
                                                        'Uncategorized'}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="px-3 py-2 align-top">
                                        <div className="flex min-w-32 flex-col gap-1.5 whitespace-normal">
                                            <span className="text-sm">
                                                {item.specimen
                                                    ?.specimen_type_name ??
                                                    item.test?.specimen_type ??
                                                    'Not yet picked'}
                                            </span>
                                            {item.specimen?.outside_sample ? (
                                                <span className="w-fit rounded-full bg-amber-50 px-2 py-0.5 text-[11px] text-amber-700">
                                                    Outside sample
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
                                                {labelize(item.workflow_stage)}
                                            </Badge>
                                            <span className="text-[11px] text-muted-foreground">
                                                {labelize(item.status)}
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
                                                        item.request
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
                                                        item.specimen
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
                                                    onAction(item, request)
                                                }
                                            >
                                                {actionLabel}
                                            </Button>
                                            {pageStage === 'enter_results' ? (
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a
                                                        href={`/laboratory/request-items/${item.id}/consumables`}
                                                    >
                                                        Consumables
                                                    </a>
                                                </Button>
                                            ) : null}
                                            {pageStage === 'view_results' &&
                                            item.result_visible ? (
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a
                                                        href={`/laboratory/request-items/${item.id}/consumables`}
                                                    >
                                                        Consumables
                                                    </a>
                                                </Button>
                                            ) : null}
                                            {pageStage === 'view_results' &&
                                            item.result_visible ? (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a
                                                        href={`/laboratory/request-items/${item.id}`}
                                                    >
                                                        Result Correction
                                                    </a>
                                                </Button>
                                            ) : null}
                                            {pageStage === 'view_results' &&
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
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}
