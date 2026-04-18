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
import { type Prescription } from '@/types/patient';
import { Edit2, Printer, Trash2 } from 'lucide-react';
import { formatDateTime, labelize, staffName } from '../visit-ordering';

const statusBadgeClasses = (status: string): string =>
    ({
        requested: 'bg-amber-100 text-amber-900',
        pending: 'bg-amber-100 text-amber-900',
        in_progress: 'bg-blue-100 text-blue-900',
        partial: 'bg-amber-100 text-amber-900',
        partially_dispensed: 'bg-amber-100 text-amber-900',
        dispensed: 'bg-emerald-100 text-emerald-900',
        completed: 'bg-emerald-100 text-emerald-900',
        fully_dispensed: 'bg-emerald-100 text-emerald-900',
        cancelled: 'bg-zinc-200 text-zinc-900',
    })[status] ?? 'bg-zinc-100 text-zinc-800';

export function PrescriptionOrdersTable({
    prescriptions,
    onEdit,
    onDelete,
    canManageOrders,
}: {
    prescriptions: Prescription[];
    onEdit?: (prescription: Prescription) => void;
    onDelete?: (prescription: Prescription) => void;
    canManageOrders: boolean;
}) {
    if (prescriptions.length === 0) {
        return (
            <div className="rounded-lg border border-dashed px-4 py-12 text-center text-sm text-muted-foreground">
                No prescriptions found for this visit.
            </div>
        );
    }

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Medication</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Prescribed By</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {prescriptions.flatMap((prescription) =>
                        prescription.items.map((item) => (
                            <TableRow key={`${prescription.id}-${item.id}`}>
                                <TableCell className="max-w-[400px]">
                                    <div className="flex flex-col gap-1">
                                        <span className="font-medium">
                                            {item.inventory_item
                                                ?.generic_name ||
                                                item.inventory_item
                                                    ?.brand_name ||
                                                'Medication'}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {[
                                                item.dosage,
                                                item.frequency,
                                                item.route,
                                                `Qty ${item.quantity}`,
                                            ].join(' / ')}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {prescription.primary_diagnosis ||
                                                'Prescription'}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        className={cn(
                                            'border-0',
                                            statusBadgeClasses(
                                                item.status ||
                                                    prescription.status,
                                            ),
                                        )}
                                    >
                                        {labelize(
                                            item.status || prescription.status,
                                        )}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {staffName(prescription.prescribedBy)}
                                </TableCell>
                                <TableCell>
                                    {formatDateTime(
                                        prescription.prescription_date,
                                    )}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-2">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                            title="Print prescription"
                                        >
                                            <a
                                                href={`/prescriptions/${prescription.id}/print`}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <Printer className="h-4 w-4" />
                                            </a>
                                        </Button>
                                        {canManageOrders ? (
                                            <>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        onEdit?.(prescription)
                                                    }
                                                    title="Edit prescription"
                                                >
                                                    <Edit2 className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        onDelete?.(prescription)
                                                    }
                                                    className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                    title="Remove prescription"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </>
                                        ) : null}
                                    </div>
                                </TableCell>
                            </TableRow>
                        )),
                    )}
                </TableBody>
            </Table>
        </div>
    );
}
