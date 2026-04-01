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
import { type FacilityServiceOrder } from '@/types/patient';
import { Edit2, Trash2 } from 'lucide-react';
import {
    formatDateTime,
    formatMoney,
    labelize,
    staffName,
} from '../visit-ordering';

const statusBadgeClasses = (status: string): string =>
    ({
        requested: 'bg-amber-100 text-amber-900',
        pending: 'bg-amber-100 text-amber-900',
        in_progress: 'bg-blue-100 text-blue-900',
        completed: 'bg-emerald-100 text-emerald-900',
        cancelled: 'bg-zinc-200 text-zinc-900',
    })[status] ?? 'bg-zinc-100 text-zinc-800';

export function ServiceOrdersTable({
    orders,
    onEdit,
    onDelete,
    canManageOrders,
}: {
    orders: FacilityServiceOrder[];
    onEdit?: (order: FacilityServiceOrder) => void;
    onDelete?: (order: FacilityServiceOrder) => void;
    canManageOrders: boolean;
}) {
    if (orders.length === 0) {
        return (
            <div className="rounded-lg border border-dashed px-4 py-12 text-center text-sm text-muted-foreground">
                No facility service orders found for this visit.
            </div>
        );
    }

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Service</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Ordered By</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Price</TableHead>
                        {canManageOrders && (
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        )}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {orders.map((order) => (
                        <TableRow key={order.id}>
                            <TableCell className="font-medium">
                                {order.service?.name ?? 'Facility service'}
                            </TableCell>
                            <TableCell>
                                <Badge
                                    className={cn(
                                        'border-0',
                                        statusBadgeClasses(order.status),
                                    )}
                                >
                                    {labelize(order.status)}
                                </Badge>
                            </TableCell>
                            <TableCell>{staffName(order.orderedBy)}</TableCell>
                            <TableCell>
                                {formatDateTime(order.ordered_at)}
                            </TableCell>
                            <TableCell>
                                {formatMoney(
                                    order.service?.quoted_price ??
                                        order.service?.selling_price ??
                                        null,
                                )}
                            </TableCell>
                            {canManageOrders && (
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-2">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => onEdit?.(order)}
                                            title="Edit order"
                                        >
                                            <Edit2 className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => onDelete?.(order)}
                                            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            title="Remove order"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            )}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
