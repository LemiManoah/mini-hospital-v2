import { LabResultDialog } from '@/pages/laboratory/components/lab-result-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { type LabRequest, type LabRequestItem } from '@/types/patient';
import { Edit, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { formatDateTime, labelize, staffName } from '../visit-ordering';

const statusBadgeClasses = (status: string): string =>
    ({
        requested: 'bg-amber-100 text-amber-900',
        pending: 'bg-amber-100 text-amber-900',
        in_progress: 'bg-blue-100 text-blue-900',
        completed: 'bg-emerald-100 text-emerald-900',
        scheduled: 'bg-sky-100 text-sky-900',
        cancelled: 'bg-zinc-200 text-zinc-900',
        rejected: 'bg-rose-100 text-rose-900',
    })[status] ?? 'bg-zinc-100 text-zinc-800';

export function LabOrdersTable({
    labRequests,
    onEdit,
    onDelete,
    onDeleteItem,
    canManageOrders,
}: {
    labRequests: LabRequest[];
    onEdit?: (request: LabRequest) => void;
    onDelete?: (request: LabRequest) => void;
    onDeleteItem?: (request: LabRequest, item: LabRequestItem) => void;
    canManageOrders: boolean;
}) {
    const [selectedResult, setSelectedResult] = useState<{
        request: LabRequest;
        item: LabRequestItem;
    } | null>(null);

    if (labRequests.length === 0) {
        return (
            <div className="rounded-lg border border-dashed px-4 py-12 text-center text-sm text-muted-foreground">
                No laboratory requests found for this visit.
            </div>
        );
    }

    const hasReleasedResults = labRequests.some((request) =>
        request.items.some((item) => item.result_visible),
    );
    const showActions = canManageOrders || hasReleasedResults;

    return (
        <>
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Test</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Priority</TableHead>
                            <TableHead>Ordered By</TableHead>
                            <TableHead>Date</TableHead>
                            {showActions && (
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            )}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {labRequests.flatMap((request) =>
                            request.items.map((item) => {
                                const itemStatus =
                                    item.workflow_stage ??
                                    item.status ??
                                    request.status;

                                return (
                                    <TableRow key={`${request.id}-${item.id}`}>
                                        <TableCell className="max-w-[320px] font-medium">
                                            <div className="flex flex-col gap-1">
                                                <span className="truncate">
                                                    {item.test?.test_name ??
                                                        'Lab request'}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {item.test?.test_code ??
                                                        'Code not assigned'}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                className={cn(
                                                    'border-0',
                                                    statusBadgeClasses(
                                                        itemStatus,
                                                    ),
                                                )}
                                            >
                                                {labelize(itemStatus)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                {labelize(request.priority)}
                                                {request.is_stat && (
                                                    <Badge
                                                        variant="destructive"
                                                        className="h-4 px-1 text-[10px]"
                                                    >
                                                        STAT
                                                    </Badge>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {staffName(request.requestedBy)}
                                        </TableCell>
                                        <TableCell>
                                            {formatDateTime(
                                                request.request_date,
                                            )}
                                        </TableCell>
                                        {showActions && (
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    {item.result_visible ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                setSelectedResult(
                                                                    {
                                                                        request,
                                                                        item,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            View Result
                                                        </Button>
                                                    ) : null}
                                                    {canManageOrders ? (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                onEdit?.(
                                                                    request,
                                                                )
                                                            }
                                                            title="Edit request"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    ) : null}
                                                    {canManageOrders ? (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                if (
                                                                    onDeleteItem
                                                                ) {
                                                                    onDeleteItem(
                                                                        request,
                                                                        item,
                                                                    );
                                                                    return;
                                                                }

                                                                onDelete?.(
                                                                    request,
                                                                );
                                                            }}
                                                            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                            title="Remove test"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    ) : null}
                                                </div>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                );
                            }),
                        )}
                    </TableBody>
                </Table>
            </div>

            <LabResultDialog
                open={selectedResult !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedResult(null);
                    }
                }}
                request={selectedResult?.request ?? null}
                item={selectedResult?.item ?? null}
            />
        </>
    );
}
