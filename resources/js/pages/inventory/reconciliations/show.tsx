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
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type InventoryReconciliationShowPageProps } from '@/types/inventory-reconciliation';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const labelize = (value: string): string =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

const badgeVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' =>
    status === 'posted'
        ? 'default'
        : status === 'rejected'
          ? 'destructive'
          : 'secondary';

export default function InventoryReconciliationShow({
    reconciliation,
}: InventoryReconciliationShowPageProps) {
    const { hasPermission } = usePermissions();
    const { flash } = usePage<SharedData>().props;

    const reviewForm = useForm({
        review_notes: reconciliation.review_notes ?? '',
    });
    const approvalForm = useForm({
        approval_notes: reconciliation.approval_notes ?? '',
    });
    const rejectionForm = useForm({
        rejection_reason: reconciliation.rejection_reason ?? '',
    });
    const submitForm = useForm({});
    const postForm = useForm({});
    const [submitDialogOpen, setSubmitDialogOpen] = useState(false);
    const [postDialogOpen, setPostDialogOpen] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/inventory/dashboard' },
        { title: 'Reconciliations', href: '/reconciliations' },
        {
            title: reconciliation.adjustment_number,
            href: `/reconciliations/${reconciliation.id}`,
        },
    ];

    const canUpdate = hasPermission('stock_adjustments.update');

    useEffect(() => {
        if (flash?.reconciliationPrompt === 'submit') {
            setSubmitDialogOpen(true);
        }

        if (flash?.reconciliationPrompt === 'post') {
            setPostDialogOpen(true);
        }
    }, [flash?.reconciliationPrompt]);

    const lineRows = reconciliation.items ?? [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Reconciliation: ${reconciliation.adjustment_number}`}
            />

            <div className="m-4 max-w-7xl space-y-6">
                <Dialog
                    open={submitDialogOpen}
                    onOpenChange={setSubmitDialogOpen}
                >
                    <DialogContent className="sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>
                                Submit Reconciliation For Review?
                            </DialogTitle>
                            <DialogDescription>
                                Review the old and new quantities below, then
                                submit this reconciliation. Submitting does not
                                change stock yet. It sends the record into the
                                review and approval workflow so another user can
                                confirm it before posting.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Item</TableHead>
                                        <TableHead className="text-right">
                                            Old Qty
                                        </TableHead>
                                        <TableHead className="text-right">
                                            New Qty
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Variance
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {lineRows.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-medium">
                                                {item.inventory_item
                                                    ?.generic_name ??
                                                    item.inventory_item?.name ??
                                                    '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {Number(
                                                    item.expected_quantity ?? 0,
                                                ).toFixed(3)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {Number(
                                                    item.actual_quantity ??
                                                        item.expected_quantity ??
                                                        0,
                                                ).toFixed(3)}
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                {Number(
                                                    item.variance_quantity ??
                                                        item.quantity_delta,
                                                ).toFixed(3)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setSubmitDialogOpen(false)}
                            >
                                Keep Draft
                            </Button>
                            <Button
                                type="button"
                                disabled={submitForm.processing}
                                onClick={() =>
                                    submitForm.post(
                                        `/reconciliations/${reconciliation.id}/submit`,
                                    )
                                }
                            >
                                Submit For Review
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog open={postDialogOpen} onOpenChange={setPostDialogOpen}>
                    <DialogContent className="sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>
                                Post Approved Reconciliation?
                            </DialogTitle>
                            <DialogDescription>
                                Posting will create the final stock movements
                                for the variances below and immediately update
                                balances for this location. Only post when the
                                approval is fully confirmed.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Item</TableHead>
                                        <TableHead className="text-right">
                                            Old Qty
                                        </TableHead>
                                        <TableHead className="text-right">
                                            New Qty
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Variance
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {lineRows.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-medium">
                                                {item.inventory_item
                                                    ?.generic_name ??
                                                    item.inventory_item?.name ??
                                                    '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {Number(
                                                    item.expected_quantity ?? 0,
                                                ).toFixed(3)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {Number(
                                                    item.actual_quantity ??
                                                        item.expected_quantity ??
                                                        0,
                                                ).toFixed(3)}
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                {Number(
                                                    item.variance_quantity ??
                                                        item.quantity_delta,
                                                ).toFixed(3)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setPostDialogOpen(false)}
                            >
                                Not Yet
                            </Button>
                            <Button
                                type="button"
                                disabled={postForm.processing}
                                onClick={() =>
                                    postForm.post(
                                        `/reconciliations/${reconciliation.id}/post`,
                                    )
                                }
                            >
                                Post Reconciliation
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {reconciliation.adjustment_number}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {reconciliation.inventory_location?.name ?? '-'} |{' '}
                            {reconciliation.reason}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/reconciliations">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div className="grid gap-4 md:grid-cols-4">
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Status
                            </span>
                            <div className="mt-1">
                                <Badge
                                    variant={badgeVariant(
                                        reconciliation.workflow_status,
                                    )}
                                >
                                    {labelize(reconciliation.workflow_status)}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Date
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDate(reconciliation.adjustment_date)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Posted At
                            </span>
                            <p className="mt-1 font-medium">
                                {formatDateTime(reconciliation.posted_at)}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Location
                            </span>
                            <p className="mt-1 font-medium">
                                {reconciliation.inventory_location?.name ?? '-'}
                            </p>
                        </div>
                    </div>

                    <div className="mt-4 grid gap-4 border-t pt-4 md:grid-cols-2">
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Notes
                            </span>
                            <p className="mt-1">
                                {reconciliation.notes ?? '-'}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Workflow
                            </span>
                            <div className="mt-1 space-y-1 text-sm">
                                <p>
                                    Submitted:{' '}
                                    {formatDateTime(
                                        reconciliation.submitted_at,
                                    )}
                                </p>
                                <p>
                                    Reviewed:{' '}
                                    {formatDateTime(reconciliation.reviewed_at)}
                                </p>
                                <p>
                                    Approved:{' '}
                                    {formatDateTime(reconciliation.approved_at)}
                                </p>
                                <p>
                                    Rejected:{' '}
                                    {formatDateTime(reconciliation.rejected_at)}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-4 grid gap-4 border-t pt-4 md:grid-cols-3">
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Review Notes
                            </span>
                            <p className="mt-1">
                                {reconciliation.review_notes ?? '-'}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Approval Notes
                            </span>
                            <p className="mt-1">
                                {reconciliation.approval_notes ?? '-'}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Rejection Reason
                            </span>
                            <p className="mt-1">
                                {reconciliation.rejection_reason ?? '-'}
                            </p>
                        </div>
                    </div>

                    {canUpdate ? (
                        <div className="mt-4 space-y-4 border-t pt-4">
                            {reconciliation.can_submit ? (
                                <Button
                                    size="sm"
                                    onClick={() => setSubmitDialogOpen(true)}
                                >
                                    Submit For Review
                                </Button>
                            ) : null}

                            {reconciliation.can_review ? (
                                <form
                                    className="grid gap-2"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        reviewForm.post(
                                            `/reconciliations/${reconciliation.id}/review`,
                                        );
                                    }}
                                >
                                    <Label htmlFor="review_notes">
                                        Review Notes
                                    </Label>
                                    <Textarea
                                        id="review_notes"
                                        value={reviewForm.data.review_notes}
                                        onChange={(event) =>
                                            reviewForm.setData(
                                                'review_notes',
                                                event.target.value,
                                            )
                                        }
                                        rows={3}
                                    />
                                    <div className="flex gap-2">
                                        <Button size="sm" type="submit">
                                            Mark Reviewed
                                        </Button>
                                    </div>
                                </form>
                            ) : null}

                            {reconciliation.can_approve ? (
                                <form
                                    className="grid gap-2"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        approvalForm.post(
                                            `/reconciliations/${reconciliation.id}/approve`,
                                        );
                                    }}
                                >
                                    <Label htmlFor="approval_notes">
                                        Approval Notes
                                    </Label>
                                    <Textarea
                                        id="approval_notes"
                                        value={approvalForm.data.approval_notes}
                                        onChange={(event) =>
                                            approvalForm.setData(
                                                'approval_notes',
                                                event.target.value,
                                            )
                                        }
                                        rows={3}
                                    />
                                    <div className="flex gap-2">
                                        <Button size="sm" type="submit">
                                            Approve
                                        </Button>
                                    </div>
                                </form>
                            ) : null}

                            {reconciliation.can_reject ? (
                                <form
                                    className="grid gap-2"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        rejectionForm.post(
                                            `/reconciliations/${reconciliation.id}/reject`,
                                        );
                                    }}
                                >
                                    <Label htmlFor="rejection_reason">
                                        Rejection Reason
                                    </Label>
                                    <Input
                                        id="rejection_reason"
                                        value={
                                            rejectionForm.data.rejection_reason
                                        }
                                        onChange={(event) =>
                                            rejectionForm.setData(
                                                'rejection_reason',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <div className="flex gap-2">
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            type="submit"
                                        >
                                            Reject
                                        </Button>
                                    </div>
                                </form>
                            ) : null}

                            {reconciliation.can_post ? (
                                <Button
                                    size="sm"
                                    onClick={() => setPostDialogOpen(true)}
                                >
                                    Post Reconciliation
                                </Button>
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 className="mb-4 text-lg font-medium">
                        Reconciliation Lines
                    </h2>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Item</TableHead>
                                    <TableHead>Batch</TableHead>
                                    <TableHead className="text-right">
                                        System Qty
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actual Qty
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Variance
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Unit Cost
                                    </TableHead>
                                    <TableHead>Notes</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {lineRows.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">
                                            {item.inventory_item
                                                ?.generic_name ??
                                                item.inventory_item?.name ??
                                                '-'}
                                        </TableCell>
                                        <TableCell>
                                            {item.inventory_batch
                                                ?.batch_number ??
                                                item.batch_number ??
                                                '-'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {Number(
                                                item.expected_quantity ?? 0,
                                            ).toFixed(3)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {Number(
                                                item.actual_quantity ??
                                                    item.expected_quantity ??
                                                    0,
                                            ).toFixed(3)}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {Number(
                                                item.variance_quantity ??
                                                    item.quantity_delta,
                                            ).toFixed(3)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {item.unit_cost !== null
                                                ? Number(
                                                      item.unit_cost,
                                                  ).toLocaleString(undefined, {
                                                      minimumFractionDigits: 2,
                                                  })
                                                : '-'}
                                        </TableCell>
                                        <TableCell>
                                            {item.notes ?? '-'}
                                        </TableCell>
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
