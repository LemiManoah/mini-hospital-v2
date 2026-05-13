import { AuditTimelineCard } from '@/components/audit-timeline-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
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
import { formatDate } from '@/lib/date';
import { usePermissions } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type InventoryReconciliationShowPageProps } from '@/types/inventory-reconciliation';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Circle, LoaderCircle, XCircle } from 'lucide-react';
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

type WorkflowStepProps = {
    label: string;
    date?: string | null;
    completed: boolean;
    rejected?: boolean;
};

function WorkflowStep({ label, date, completed, rejected }: WorkflowStepProps) {
    const Icon = rejected ? XCircle : completed ? CheckCircle2 : Circle;

    return (
        <div className="flex flex-col items-center gap-1">
            <Icon
                className={cn(
                    'h-5 w-5',
                    rejected
                        ? 'text-red-500'
                        : completed
                          ? 'text-primary'
                          : 'text-zinc-300 dark:text-zinc-600',
                )}
            />
            <span
                className={cn(
                    'text-xs font-medium',
                    rejected
                        ? 'text-red-500'
                        : completed
                          ? 'text-foreground'
                          : 'text-muted-foreground',
                )}
            >
                {label}
            </span>
            {date ? (
                <span className="text-xs text-muted-foreground">
                    {formatDate(date)}
                </span>
            ) : null}
        </div>
    );
}

function StepConnector({ completed }: { completed: boolean }) {
    return (
        <div
            className={cn(
                'mb-5 h-0.5 flex-1',
                completed ? 'bg-primary' : 'bg-zinc-200 dark:bg-zinc-700',
            )}
        />
    );
}

export default function InventoryReconciliationShow({
    reconciliation,
    audit_activity,
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
    const lineRows = reconciliation.items ?? [];

    useEffect(() => {
        if (flash?.reconciliationPrompt === 'submit') {
            setSubmitDialogOpen(true);
        }

        if (flash?.reconciliationPrompt === 'post') {
            setPostDialogOpen(true);
        }
    }, [flash?.reconciliationPrompt]);

    const isRejected = !!reconciliation.rejected_at;

    const confirmationTable = (
        <div className="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Item</TableHead>
                        <TableHead className="text-right">System Qty</TableHead>
                        <TableHead className="text-right">Actual Qty</TableHead>
                        <TableHead className="text-right">Variance</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {lineRows.map((item) => {
                        const variance = Number(
                            item.variance_quantity ?? item.quantity_delta,
                        );
                        return (
                            <TableRow key={item.id}>
                                <TableCell className="font-medium">
                                    {item.inventory_item?.generic_name ??
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
                                <TableCell
                                    className={cn(
                                        'text-right font-medium',
                                        variance < 0 &&
                                            'text-red-600 dark:text-red-400',
                                        variance > 0 &&
                                            'text-green-600 dark:text-green-400',
                                    )}
                                >
                                    {variance > 0 ? '+' : ''}
                                    {variance.toFixed(3)}
                                </TableCell>
                            </TableRow>
                        );
                    })}
                </TableBody>
            </Table>
        </div>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Reconciliation: ${reconciliation.adjustment_number}`}
            />

            {/* Submit dialog */}
            <Dialog open={submitDialogOpen} onOpenChange={setSubmitDialogOpen}>
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Submit for Review?</DialogTitle>
                    </DialogHeader>
                    {confirmationTable}
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={submitForm.processing}
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
                                    {
                                        onSuccess: () =>
                                            setSubmitDialogOpen(false),
                                    },
                                )
                            }
                        >
                            {submitForm.processing ? (
                                <>
                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    Submitting…
                                </>
                            ) : (
                                'Submit For Review'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Post dialog */}
            <Dialog open={postDialogOpen} onOpenChange={setPostDialogOpen}>
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Post Reconciliation?</DialogTitle>
                    </DialogHeader>
                    {confirmationTable}
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={postForm.processing}
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
                                    {
                                        onSuccess: () =>
                                            setPostDialogOpen(false),
                                    },
                                )
                            }
                        >
                            {postForm.processing ? (
                                <>
                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    Posting…
                                </>
                            ) : (
                                'Post Reconciliation'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="m-4 max-w-7xl space-y-6">
                {/* Header strip */}
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-0.5">
                        <div className="flex items-center gap-3">
                            <h1 className="font-mono text-2xl font-semibold">
                                {reconciliation.adjustment_number}
                            </h1>
                            <Badge
                                variant={badgeVariant(
                                    reconciliation.workflow_status,
                                )}
                            >
                                {labelize(reconciliation.workflow_status)}
                            </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {reconciliation.inventory_location?.name ?? '-'} ·{' '}
                            {formatDate(reconciliation.adjustment_date)}
                        </p>
                        <p className="text-sm">{reconciliation.reason}</p>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {canUpdate && reconciliation.can_submit ? (
                            <Button onClick={() => setSubmitDialogOpen(true)}>
                                Submit For Review
                            </Button>
                        ) : null}
                        {canUpdate && reconciliation.can_post ? (
                            <Button onClick={() => setPostDialogOpen(true)}>
                                Post Reconciliation
                            </Button>
                        ) : null}
                        <Button variant="outline" asChild>
                            <Link href="/reconciliations">Back</Link>
                        </Button>
                    </div>
                </div>

                {/* Workflow progress bar */}
                <div className="rounded border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div className="flex items-center gap-2">
                        <WorkflowStep label="Draft" completed={true} />
                        <StepConnector
                            completed={!!reconciliation.submitted_at}
                        />
                        <WorkflowStep
                            label="Submitted"
                            date={reconciliation.submitted_at}
                            completed={!!reconciliation.submitted_at}
                        />
                        <StepConnector
                            completed={
                                !!reconciliation.reviewed_at || isRejected
                            }
                        />
                        {isRejected ? (
                            <WorkflowStep
                                label="Rejected"
                                date={reconciliation.rejected_at}
                                completed={true}
                                rejected={true}
                            />
                        ) : (
                            <>
                                <WorkflowStep
                                    label="Reviewed"
                                    date={reconciliation.reviewed_at}
                                    completed={!!reconciliation.reviewed_at}
                                />
                                <StepConnector
                                    completed={!!reconciliation.approved_at}
                                />
                                <WorkflowStep
                                    label="Approved"
                                    date={reconciliation.approved_at}
                                    completed={!!reconciliation.approved_at}
                                />
                                <StepConnector
                                    completed={!!reconciliation.posted_at}
                                />
                                <WorkflowStep
                                    label="Posted"
                                    date={reconciliation.posted_at}
                                    completed={!!reconciliation.posted_at}
                                />
                            </>
                        )}
                    </div>
                </div>

                {/* Conditional notes — only the relevant one */}
                {reconciliation.rejection_reason ? (
                    <div className="rounded border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
                        <p className="text-sm font-medium text-red-700 dark:text-red-400">
                            Rejection Reason
                        </p>
                        <p className="mt-1 text-sm text-red-600 dark:text-red-300">
                            {reconciliation.rejection_reason}
                        </p>
                    </div>
                ) : null}

                {!reconciliation.rejection_reason &&
                reconciliation.review_notes ? (
                    <div className="rounded border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <p className="text-sm font-medium text-muted-foreground">
                            Review Notes
                        </p>
                        <p className="mt-1 text-sm">
                            {reconciliation.review_notes}
                        </p>
                    </div>
                ) : null}

                {!reconciliation.rejection_reason &&
                reconciliation.approval_notes ? (
                    <div className="rounded border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <p className="text-sm font-medium text-muted-foreground">
                            Approval Notes
                        </p>
                        <p className="mt-1 text-sm">
                            {reconciliation.approval_notes}
                        </p>
                    </div>
                ) : null}

                {reconciliation.notes ? (
                    <div className="rounded border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <p className="text-sm font-medium text-muted-foreground">
                            Notes
                        </p>
                        <p className="mt-1 text-sm">{reconciliation.notes}</p>
                    </div>
                ) : null}

                {/* Action panel — one section at a time */}
                {canUpdate ? (
                    <>
                        {reconciliation.can_review ? (
                            <div className="rounded border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <p className="mb-3 text-sm font-medium">
                                    Mark as Reviewed
                                </p>
                                <form
                                    className="grid gap-3"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        reviewForm.post(
                                            `/reconciliations/${reconciliation.id}/review`,
                                        );
                                    }}
                                >
                                    <div className="grid gap-1.5">
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
                                    </div>
                                    <div>
                                        <Button size="sm" type="submit">
                                            Mark Reviewed
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        ) : null}

                        {reconciliation.can_approve ||
                        reconciliation.can_reject ? (
                            <div
                                className={cn(
                                    'grid gap-4',
                                    reconciliation.can_approve &&
                                        reconciliation.can_reject &&
                                        'md:grid-cols-2',
                                )}
                            >
                                {reconciliation.can_approve ? (
                                    <div className="rounded border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                        <p className="mb-3 text-sm font-medium">
                                            Approve
                                        </p>
                                        <form
                                            className="grid gap-3"
                                            onSubmit={(event) => {
                                                event.preventDefault();
                                                approvalForm.post(
                                                    `/reconciliations/${reconciliation.id}/approve`,
                                                );
                                            }}
                                        >
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="approval_notes">
                                                    Approval Notes
                                                </Label>
                                                <Textarea
                                                    id="approval_notes"
                                                    value={
                                                        approvalForm.data
                                                            .approval_notes
                                                    }
                                                    onChange={(event) =>
                                                        approvalForm.setData(
                                                            'approval_notes',
                                                            event.target.value,
                                                        )
                                                    }
                                                    rows={3}
                                                />
                                            </div>
                                            <div>
                                                <Button size="sm" type="submit">
                                                    Approve
                                                </Button>
                                            </div>
                                        </form>
                                    </div>
                                ) : null}

                                {reconciliation.can_reject ? (
                                    <div className="rounded border border-red-100 bg-white p-4 shadow-sm dark:border-red-900/40 dark:bg-zinc-900">
                                        <p className="mb-3 text-sm font-medium text-red-600 dark:text-red-400">
                                            Reject
                                        </p>
                                        <form
                                            className="grid gap-3"
                                            onSubmit={(event) => {
                                                event.preventDefault();
                                                rejectionForm.post(
                                                    `/reconciliations/${reconciliation.id}/reject`,
                                                );
                                            }}
                                        >
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="rejection_reason">
                                                    Rejection Reason
                                                </Label>
                                                <Input
                                                    id="rejection_reason"
                                                    value={
                                                        rejectionForm.data
                                                            .rejection_reason
                                                    }
                                                    onChange={(event) =>
                                                        rejectionForm.setData(
                                                            'rejection_reason',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                            </div>
                                            <div>
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    type="submit"
                                                >
                                                    Reject
                                                </Button>
                                            </div>
                                        </form>
                                    </div>
                                ) : null}
                            </div>
                        ) : null}
                    </>
                ) : null}

                {/* Reconciliation lines */}
                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 className="mb-4 text-base font-medium">Lines</h2>
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
                                {lineRows.map((item) => {
                                    const variance = Number(
                                        item.variance_quantity ??
                                            item.quantity_delta,
                                    );

                                    return (
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
                                            <TableCell
                                                className={cn(
                                                    'text-right font-medium',
                                                    variance < 0 &&
                                                        'text-red-600 dark:text-red-400',
                                                    variance > 0 &&
                                                        'text-green-600 dark:text-green-400',
                                                )}
                                            >
                                                {variance !== 0
                                                    ? (variance > 0
                                                          ? '+'
                                                          : '') +
                                                      variance.toFixed(3)
                                                    : '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {item.unit_cost !== null
                                                    ? Number(
                                                          item.unit_cost,
                                                      ).toLocaleString(
                                                          undefined,
                                                          {
                                                              minimumFractionDigits: 2,
                                                          },
                                                      )
                                                    : '-'}
                                            </TableCell>
                                            <TableCell>
                                                {item.notes ?? '-'}
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <AuditTimelineCard
                    title="Audit Log"
                    entries={audit_activity}
                    emptyMessage="No audit activity recorded yet."
                />
            </div>
        </AppLayout>
    );
}
