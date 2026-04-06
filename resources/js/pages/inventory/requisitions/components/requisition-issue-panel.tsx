import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type {
    InventoryRequisition,
    InventoryRequisitionAvailableBatch,
} from '@/types/inventory-requisition';
import { PlusCircle, Trash2 } from 'lucide-react';

import type { IssueForm } from './types';

type Props = {
    requisition: InventoryRequisition;
    canIssue: boolean;
    availableBatchBalances: InventoryRequisitionAvailableBatch[];
    issueForm: IssueForm;
    onIssueLineChange: (
        index: number,
        field: 'issue_quantity' | 'notes',
        value: string,
    ) => void;
    onAddAllocation: (lineIndex: number) => void;
    onRemoveAllocation: (lineIndex: number, allocationIndex: number) => void;
    onUpdateAllocation: (
        lineIndex: number,
        allocationIndex: number,
        field: 'inventory_batch_id' | 'quantity',
        value: string,
    ) => void;
    submitUrl: string;
};

export function RequisitionIssuePanel({
    requisition,
    canIssue,
    availableBatchBalances,
    issueForm,
    onIssueLineChange,
    onAddAllocation,
    onRemoveAllocation,
    onUpdateAllocation,
    submitUrl,
}: Props) {
    const issueReadyLines = (requisition.items ?? []).filter(
        (line) => line.remaining_quantity > 0,
    );

    if (!canIssue || !requisition.can_issue) {
        return null;
    }

    const batchOptionsFor = (inventoryItemId: string) =>
        availableBatchBalances
            .filter((batch) => batch.inventory_item_id === inventoryItemId)
            .map((batch) => ({
                value: batch.inventory_batch_id,
                label: `${batch.batch_number ?? 'No batch'} | Qty ${batch.quantity.toFixed(3)}${batch.expiry_date ? ` | Exp ${batch.expiry_date}` : ''}`,
            }));

    return (
        <form
            className="space-y-4"
            onSubmit={(event) => {
                event.preventDefault();
                issueForm.post(submitUrl);
            }}
        >
            <div>
                <h2 className="text-lg font-medium">Issue Approved Stock</h2>
                <p className="text-sm text-muted-foreground">
                    Select the source batches and quantities to move from the
                    main store into the requesting unit.
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
                                    {line.inventory_item?.generic_name ??
                                        line.inventory_item?.name ??
                                        '-'}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Approved:{' '}
                                    {line.approved_quantity.toFixed(3)} | Issued:{' '}
                                    {line.issued_quantity.toFixed(3)} | Remaining:{' '}
                                    {line.remaining_quantity.toFixed(3)}
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() => onAddAllocation(lineIndex)}
                            >
                                <PlusCircle className="mr-2 h-4 w-4" />
                                Add Batch
                            </Button>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Issue Quantity</Label>
                                <Input
                                    type="number"
                                    step="any"
                                    min="0"
                                    value={
                                        issueForm.data.items[lineIndex]
                                            ?.issue_quantity ?? ''
                                    }
                                    onChange={(event) =>
                                        onIssueLineChange(
                                            lineIndex,
                                            'issue_quantity',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={
                                        issueForm.errors[
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
                                        issueForm.data.items[lineIndex]?.notes ??
                                        ''
                                    }
                                    onChange={(event) =>
                                        onIssueLineChange(
                                            lineIndex,
                                            'notes',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <div className="mt-4 space-y-3">
                            {issueForm.data.items[lineIndex]?.allocations.length ? (
                                issueForm.data.items[lineIndex].allocations.map(
                                    (allocation, allocationIndex) => (
                                        <div
                                            key={`${line.id}-${allocationIndex}`}
                                            className="grid gap-3 rounded border border-dashed border-zinc-200 p-3 md:grid-cols-[1.6fr_1fr_auto] dark:border-zinc-700"
                                        >
                                            <div className="grid gap-2">
                                                <Label>Source Batch</Label>
                                                <SearchableSelect
                                                    options={batchOptionsFor(
                                                        line.inventory_item_id,
                                                    )}
                                                    value={
                                                        allocation.inventory_batch_id
                                                    }
                                                    onValueChange={(value) =>
                                                        onUpdateAllocation(
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
                                                        issueForm.errors[
                                                            `items.${lineIndex}.allocations.${allocationIndex}.inventory_batch_id` as keyof typeof issueForm.errors
                                                        ]
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Quantity</Label>
                                                <Input
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    value={allocation.quantity}
                                                    onChange={(event) =>
                                                        onUpdateAllocation(
                                                            lineIndex,
                                                            allocationIndex,
                                                            'quantity',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        issueForm.errors[
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
                                                        onRemoveAllocation(
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
                                    No source batches selected yet.
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
                <Label htmlFor="issued_notes">Issue Notes</Label>
                <Textarea
                    id="issued_notes"
                    rows={3}
                    value={issueForm.data.issued_notes}
                    onChange={(event) =>
                        issueForm.setData('issued_notes', event.target.value)
                    }
                />
                <InputError message={issueForm.errors.issued_notes} />
            </div>

            <Button size="sm" type="submit">
                Post Issue
            </Button>
        </form>
    );
}
