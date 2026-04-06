import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { InventoryRequisition } from '@/types/inventory-requisition';

type Props = {
    requisition: InventoryRequisition;
};

export function RequisitionLinesTable({ requisition }: Props) {
    const lines = requisition.items ?? [];

    return (
        <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 className="mb-4 text-lg font-medium">Requisition Lines</h2>
            <div className="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Item</TableHead>
                            <TableHead className="text-right">
                                Requested
                            </TableHead>
                            <TableHead className="text-right">
                                Approved
                            </TableHead>
                            <TableHead className="text-right">Issued</TableHead>
                            <TableHead className="text-right">
                                Remaining
                            </TableHead>
                            <TableHead>Issue History</TableHead>
                            <TableHead>Notes</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {lines.map((line) => (
                            <TableRow key={line.id}>
                                <TableCell className="font-medium">
                                    {line.inventory_item?.generic_name ??
                                        line.inventory_item?.name ??
                                        '-'}
                                </TableCell>
                                <TableCell className="text-right">
                                    {line.requested_quantity.toFixed(3)}
                                </TableCell>
                                <TableCell className="text-right">
                                    {line.approved_quantity.toFixed(3)}
                                </TableCell>
                                <TableCell className="text-right">
                                    {line.issued_quantity.toFixed(3)}
                                </TableCell>
                                <TableCell className="text-right">
                                    {line.remaining_quantity.toFixed(3)}
                                </TableCell>
                                <TableCell className="text-sm">
                                    {line.issue_history?.length ? (
                                        <div className="space-y-1">
                                            {line.issue_history.map(
                                                (entry, index) => (
                                                    <div
                                                        key={`${line.id}-${index}`}
                                                    >
                                                        {entry.quantity.toFixed(
                                                            3,
                                                        )}{' '}
                                                        from{' '}
                                                        {entry.batch_number ??
                                                            'No batch'}
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    ) : (
                                        '-'
                                    )}
                                </TableCell>
                                <TableCell>{line.notes ?? '-'}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
