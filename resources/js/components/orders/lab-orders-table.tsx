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
import { type LabRequest } from '@/types/patient';
import { Edit2, Trash2 } from 'lucide-react';
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
    canManageOrders,
}: {
    labRequests: LabRequest[];
    onEdit?: (request: LabRequest) => void;
    onDelete?: (request: LabRequest) => void;
    canManageOrders: boolean;
}) {
    if (labRequests.length === 0) {
        return (
            <div className="rounded-lg border border-dashed px-4 py-12 text-center text-sm text-muted-foreground">
                No laboratory requests found for this visit.
            </div>
        );
    }

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Test</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Priority</TableHead>
                        <TableHead>Ordered By</TableHead>
                        <TableHead>Date</TableHead>
                        {canManageOrders && (
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        )}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {labRequests.flatMap((request) => {
                        const items = request.items;

                        if (items.length === 0) {
                            return [];
                        }

                        return items.map((item, index) => {
                            const itemStatus =
                                item.workflow_stage ?? item.status ?? request.status;

                            return (
                                <TableRow key={`${request.id}-${item.id}`}>
                                    <TableCell className="max-w-[320px] font-medium">
                                        <div className="flex flex-col gap-1">
                                            <span className="truncate">
                                                {item.test?.test_name ?? 'Lab request'}
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
                                                statusBadgeClasses(itemStatus),
                                            )}
                                        >
                                            {labelize(itemStatus)}
                                        </Badge>
                                    </TableCell>
                                    {index === 0 && (
                                        <TableCell rowSpan={items.length}>
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
                                    )}
                                    {index === 0 && (
                                        <TableCell rowSpan={items.length}>
                                            {staffName(request.requestedBy)}
                                        </TableCell>
                                    )}
                                    {index === 0 && (
                                        <TableCell rowSpan={items.length}>
                                            {formatDateTime(request.request_date)}
                                        </TableCell>
                                    )}
                                    {canManageOrders && index === 0 && (
                                        <TableCell
                                            rowSpan={items.length}
                                            className="text-right align-top"
                                        >
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => onEdit?.(request)}
                                                    title="Edit request"
                                                >
                                                    <Edit2 className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => onDelete?.(request)}
                                                    className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                    title="Remove request"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    )}
                                </TableRow>
                            );
                        });
                    })}
                </TableBody>
            </Table>
        </div>
    );
}
