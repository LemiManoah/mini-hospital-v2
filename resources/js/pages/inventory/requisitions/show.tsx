import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { formatDate, formatDateTime } from '@/lib/date';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type InventoryRequisitionShowPageProps } from '@/types/inventory-requisition';
import { Head, Link, useForm } from '@inertiajs/react';
import { PlusCircle, Trash2 } from 'lucide-react';

type ApproveLine = {
    inventory_requisition_item_id: string;
    approved_quantity: string;
};

type AllocationLine = {
    inventory_batch_id: string;
    quantity: string;
};

type IssueLine = {
    inventory_requisition_item_id: string;
    issue_quantity: string;
    notes: string;
    allocations: AllocationLine[];
};

const badgeVariant = (
    status: string | null,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'fulfilled') {
        return 'default';
    }

    if (status === 'rejected' || status === 'cancelled') {
        return 'destructive';
    }

    if (status === 'approved' || status === 'partially_issued') {
        return 'outline';
    }

    return 'secondary';
};

const emptyAllocation = (): AllocationLine => ({
    inventory_batch_id: '',
    quantity: '',
});

export default function InventoryRequisitionShow({
    requisition,
    availableBatchBalances,
}: InventoryRequisitionShowPageProps) {
    const { hasPermission } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/inventory/dashboard' },
        { title: 'Requisitions', href: '/inventory-requisitions' },
        {
            title: requisition.requisition_number,
            href: `/inventory-requisitions/${requisition.id}`,
        },
    ];

    const canUpdate = hasPermission('inventory_requisitions.update');
    const lines = requisition.items ?? [];
    const issueReadyLines = lines.filter((line) => line.remaining_quantity > 0);

    const submitForm = useForm({});
    const approveForm = useForm<{
        approval_notes: string;
        items: ApproveLine[];
    }>({
        approval_notes: requisition.approval_notes ?? '',
        items: lines.map((line) => ({
            inventory_requisition_item_id: line.id,
            approved_quantity: line.requested_quantity.toFixed(3),
        })),
    });
    const rejectForm = useForm({
        rejection_reason: requisition.rejection_reason ?? '',
    });
    const issueForm = useForm<{
        issued_notes: string;
        items: IssueLine[];
    }>({
        issued_notes: requisition.issued_notes ?? '',
        items: issueReadyLines.map((line) => ({
            inventory_requisition_item_id: line.id,
            issue_quantity: '',
            notes: '',
            allocations: [],
        })),
    });

    const updateApproveLine = (index: number, value: string) => {
        const updated = [...approveForm.data.items];
        updated[index] = {
            ...updated[index],
            approved_quantity: value,
        };
        approveForm.setData('items', updated);
    };

    const updateIssueLine = (
        index: number,
        field: keyof Omit<IssueLine, 'allocations' | 'inventory_requisition_item_id'>,
        value: string,
    ) => {
        const updated = [...issueForm.data.items];
        updated[index] = {
            ...updated[index],
            [field]: value,
        };
        issueForm.setData('items', updated);
    };

    const addAllocation = (lineIndex: number) => {
        const updated = [...issueForm.data.items];
        updated[lineIndex] = {
            ...updated[lineIndex],
            allocations: [...updated[lineIndex].allocations, emptyAllocation()],
        };
        issueForm.setData('items', updated);
    };

    const removeAllocation = (lineIndex: number, allocationIndex: number) => {
        const updated = [...issueForm.data.items];
        updated[lineIndex] = {
            ...updated[lineIndex],
            allocations:
                updated[lineIndex].allocations.length === 1
                    ? []
                    : updated[lineIndex].allocations.filter(
                          (_, index) => index !== allocationIndex,
                      ),
        };
        issueForm.setData('items', updated);
    };

    const updateAllocation = (
        lineIndex: number,
        allocationIndex: number,
        field: keyof AllocationLine,
        value: string,
    ) => {
        const updated = [...issueForm.data.items];
        const allocations = [...updated[lineIndex].allocations];
        allocations[allocationIndex] = {
            ...allocations[allocationIndex],
            [field]: value,
        };
        updated[lineIndex] = {
            ...updated[lineIndex],
            allocations,
        };
        issueForm.setData('items', updated);
    };

    const batchOptionsFor = (inventoryItemId: string) =>
        availableBatchBalances
            .filter((batch) => batch.inventory_item_id === inventoryItemId)
            .map((batch) => ({
                value: batch.inventory_batch_id,
                label: `${batch.batch_number ?? 'No batch'} | Qty ${batch.quantity.toFixed(3)}${batch.expiry_date ? ` | Exp ${batch.expiry_date}` : ''}`,
            }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Requisition: ${requisition.requisition_number}`} />

            <div className="m-4 max-w-7xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {requisition.requisition_number}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {requisition.source_location?.name ?? '-'} to{' '}
                            {requisition.destination_location?.name ?? '-'}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/inventory-requisitions">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div className="grid gap-4 md:grid-cols-4">
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Status
                            </span>
                            <div className="mt-1">
                                <Badge variant={badgeVariant(requisition.status)}>
                                    {requisition.status_label ?? '-'}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Priority
                            </span>
                            <p className="mt-1 font-medium">
                                {requisition.priority_label ?? '-'}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Date
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDate(requisition.requisition_date)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Issued At
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDateTime(requisition.issued_at)}
                            </p>
                        </div>
                    </div>

                    <div className="mt-4 grid gap-4 border-t pt-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Source
                            </span>
                            <p className="mt-1 font-medium">
                                {requisition.source_location?.name ?? '-'}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Destination
                            </span>
                            <p className="mt-1 font-medium">
                                {requisition.destination_location?.name ?? '-'}
                            </p>
                        </div>
                    </div>

                    <div className="mt-4 grid gap-4 border-t pt-4 md:grid-cols-3">
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Notes
                            </span>
                            <p className="mt-1">{requisition.notes ?? '-'}</p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Approval Notes
                            </span>
                            <p className="mt-1">
                                {requisition.approval_notes ?? '-'}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Rejection Reason
                            </span>
                            <p className="mt-1">
                                {requisition.rejection_reason ?? '-'}
                            </p>
                        </div>
                    </div>

                    {canUpdate ? (
                        <div className="mt-4 space-y-6 border-t pt-4">
                            {requisition.can_submit ? (
                                <Button
                                    size="sm"
                                    onClick={() =>
                                        submitForm.post(
                                            `/inventory-requisitions/${requisition.id}/submit`,
                                        )
                                    }
                                >
                                    Submit For Approval
                                </Button>
                            ) : null}

                            {requisition.can_approve ? (
                                <form
                                    className="space-y-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        approveForm.post(
                                            `/inventory-requisitions/${requisition.id}/approve`,
                                        );
                                    }}
                                >
                                    <div>
                                        <h2 className="text-lg font-medium">
                                            Approve Quantities
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            Set the quantity each line is
                                            allowed to issue from the source
                                            location.
                                        </p>
                                    </div>

                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Item</TableHead>
                                                    <TableHead className="text-right">
                                                        Requested
                                                    </TableHead>
                                                    <TableHead className="w-48 text-right">
                                                        Approved
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {lines.map((line, index) => (
                                                    <TableRow key={line.id}>
                                                        <TableCell className="font-medium">
                                                            {line.inventory_item
                                                                ?.generic_name ??
                                                                line
                                                                    .inventory_item
                                                                    ?.name ??
                                                                '-'}
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            {line.requested_quantity.toFixed(
                                                                3,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="align-top">
                                                            <Input
                                                                type="number"
                                                                step="any"
                                                                min="0"
                                                                value={
                                                                    approveForm
                                                                        .data
                                                                        .items[
                                                                        index
                                                                    ]
                                                                        ?.approved_quantity ??
                                                                    ''
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    updateApproveLine(
                                                                        index,
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    approveForm
                                                                        .errors[
                                                                        `items.${index}.approved_quantity` as keyof typeof approveForm.errors
                                                                    ]
                                                                }
                                                            />
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="approval_notes">
                                            Approval Notes
                                        </Label>
                                        <Textarea
                                            id="approval_notes"
                                            rows={3}
                                            value={
                                                approveForm.data.approval_notes
                                            }
                                            onChange={(event) =>
                                                approveForm.setData(
                                                    'approval_notes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                approveForm.errors
                                                    .approval_notes
                                            }
                                        />
                                    </div>

                                    <Button size="sm" type="submit">
                                        Approve Requisition
                                    </Button>
                                </form>
                            ) : null}

                            {requisition.can_reject ? (
                                <form
                                    className="grid gap-2"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        rejectForm.post(
                                            `/inventory-requisitions/${requisition.id}/reject`,
                                        );
                                    }}
                                >
                                    <Label htmlFor="rejection_reason">
                                        Rejection Reason
                                    </Label>
                                    <Input
                                        id="rejection_reason"
                                        value={rejectForm.data.rejection_reason}
                                        onChange={(event) =>
                                            rejectForm.setData(
                                                'rejection_reason',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            rejectForm.errors.rejection_reason
                                        }
                                    />
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        type="submit"
                                    >
                                        Reject Requisition
                                    </Button>
                                </form>
                            ) : null}

                            {requisition.can_issue ? (
                                <form
                                    className="space-y-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        issueForm.post(
                                            `/inventory-requisitions/${requisition.id}/issue`,
                                        );
                                    }}
                                >
                                    <div>
                                        <h2 className="text-lg font-medium">
                                            Issue Stock
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            Select the source batches and
                                            quantities to move from the source
                                            location to the destination
                                            location.
                                        </p>
                                    </div>

                                    <InputError message={issueForm.errors.items} />

                                    <div className="space-y-6">
                                        {issueReadyLines.map((line, lineIndex) => (
                                            <div
                                                key={line.id}
                                                className="rounded border border-zinc-200 p-4 dark:border-zinc-800"
                                            >
                                                <div className="mb-3 flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                                                    <div>
                                                        <h3 className="font-medium">
                                                            {line.inventory_item
                                                                ?.generic_name ??
                                                                line
                                                                    .inventory_item
                                                                    ?.name ??
                                                                '-'}
                                                        </h3>
                                                        <p className="text-sm text-muted-foreground">
                                                            Approved:{' '}
                                                            {line.approved_quantity.toFixed(
                                                                3,
                                                            )}{' '}
                                                            | Issued:{' '}
                                                            {line.issued_quantity.toFixed(
                                                                3,
                                                            )}{' '}
                                                            | Remaining:{' '}
                                                            {line.remaining_quantity.toFixed(
                                                                3,
                                                            )}
                                                        </p>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            addAllocation(
                                                                lineIndex,
                                                            )
                                                        }
                                                    >
                                                        <PlusCircle className="mr-2 h-4 w-4" />
                                                        Add Batch
                                                    </Button>
                                                </div>

                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Issue Quantity
                                                        </Label>
                                                        <Input
                                                            type="number"
                                                            step="any"
                                                            min="0"
                                                            value={
                                                                issueForm.data
                                                                    .items[
                                                                    lineIndex
                                                                ]
                                                                    ?.issue_quantity ??
                                                                ''
                                                            }
                                                            onChange={(event) =>
                                                                updateIssueLine(
                                                                    lineIndex,
                                                                    'issue_quantity',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                        <InputError
                                                            message={
                                                                issueForm
                                                                    .errors[
                                                                    `items.${lineIndex}.issue_quantity` as keyof typeof issueForm.errors
                                                                ]
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-2">
                                                        <Label>Line Notes</Label>
                                                        <Textarea
                                                            rows={2}
                                                            value={
                                                                issueForm.data
                                                                    .items[
                                                                    lineIndex
                                                                ]?.notes ?? ''
                                                            }
                                                            onChange={(event) =>
                                                                updateIssueLine(
                                                                    lineIndex,
                                                                    'notes',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                </div>

                                                <div className="mt-4 space-y-3">
                                                    {issueForm.data.items[
                                                        lineIndex
                                                    ]?.allocations.length ? (
                                                        issueForm.data.items[
                                                            lineIndex
                                                        ].allocations.map(
                                                            (
                                                                allocation,
                                                                allocationIndex,
                                                            ) => (
                                                                <div
                                                                    key={`${line.id}-${allocationIndex}`}
                                                                    className="grid gap-3 rounded border border-dashed border-zinc-200 p-3 md:grid-cols-[1.6fr_1fr_auto] dark:border-zinc-700"
                                                                >
                                                                    <div className="grid gap-2">
                                                                        <Label>
                                                                            Source
                                                                            Batch
                                                                        </Label>
                                                                        <SearchableSelect
                                                                            options={batchOptionsFor(
                                                                                line.inventory_item_id,
                                                                            )}
                                                                            value={
                                                                                allocation.inventory_batch_id
                                                                            }
                                                                            onValueChange={(
                                                                                value,
                                                                            ) =>
                                                                                updateAllocation(
                                                                                    lineIndex,
                                                                                    allocationIndex,
                                                                                    'inventory_batch_id',
                                                                                    value,
                                                                                )
                                                                            }
                                                                            placeholder="Select batch"
                                                                            emptyMessage="No matching batches."
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                issueForm
                                                                                    .errors[
                                                                                    `items.${lineIndex}.allocations.${allocationIndex}.inventory_batch_id` as keyof typeof issueForm.errors
                                                                                ]
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label>
                                                                            Quantity
                                                                        </Label>
                                                                        <Input
                                                                            type="number"
                                                                            step="any"
                                                                            min="0"
                                                                            value={
                                                                                allocation.quantity
                                                                            }
                                                                            onChange={(
                                                                                event,
                                                                            ) =>
                                                                                updateAllocation(
                                                                                    lineIndex,
                                                                                    allocationIndex,
                                                                                    'quantity',
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                issueForm
                                                                                    .errors[
                                                                                    `items.${lineIndex}.allocations.${allocationIndex}.quantity` as keyof typeof issueForm.errors
                                                                                ]
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <div className="flex items-end">
                                                                        <Button
                                                                            type="button"
                                                                            size="icon"
                                                                            variant="ghost"
                                                                            onClick={() =>
                                                                                removeAllocation(
                                                                                    lineIndex,
                                                                                    allocationIndex,
                                                                                )
                                                                            }
                                                                        >
                                                                            <Trash2 className="h-4 w-4" />
                                                                        </Button>
                                                                    </div>
                                                                </div>
                                                            ),
                                                        )
                                                    ) : (
                                                        <p className="text-sm text-muted-foreground">
                                                            No source batches
                                                            selected yet.
                                                        </p>
                                                    )}
                                                    <InputError
                                                        message={
                                                            issueForm.errors[
                                                                `items.${lineIndex}.allocations` as keyof typeof issueForm.errors
                                                            ]
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="issued_notes">
                                            Issue Notes
                                        </Label>
                                        <Textarea
                                            id="issued_notes"
                                            rows={3}
                                            value={issueForm.data.issued_notes}
                                            onChange={(event) =>
                                                issueForm.setData(
                                                    'issued_notes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                issueForm.errors.issued_notes
                                            }
                                        />
                                    </div>

                                    <Button size="sm" type="submit">
                                        Post Issue
                                    </Button>
                                </form>
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 className="mb-4 text-lg font-medium">
                        Requisition Lines
                    </h2>
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
                                    <TableHead className="text-right">
                                        Issued
                                    </TableHead>
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
                                                            <div key={`${line.id}-${index}`}>
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
            </div>
        </AppLayout>
    );
}
