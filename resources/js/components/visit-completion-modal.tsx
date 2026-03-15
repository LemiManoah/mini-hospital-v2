import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Form } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, LoaderCircle } from 'lucide-react';
import { ReactNode } from 'react';

interface CompletionCheck {
    can_complete: boolean;
    has_pending_services: boolean;
    pending_services_count: number;
    has_unpaid_balance: boolean;
    unpaid_balance: number;
    blocking_reasons: string[];
    warning_messages: string[];
}

interface VisitCompletionModalProps {
    visitId: string;
    visitNumber: string;
    completionCheck?: CompletionCheck | null;
    redirectTo?: 'show' | 'index';
    trigger?: ReactNode;
    onSuccess?: () => void;
}

export default function VisitCompletionModal({
    visitId,
    visitNumber,
    completionCheck,
    redirectTo = 'show',
    trigger,
    onSuccess,
}: VisitCompletionModalProps) {
    const blockingReasons = completionCheck?.blocking_reasons ?? [];
    const warningMessages = completionCheck?.warning_messages ?? [];
    const isBlocked = completionCheck?.can_complete === false;

    return (
        <Dialog>
            <DialogTrigger asChild>
                {trigger || <Button size="sm">Complete Visit</Button>}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Complete Visit {visitNumber}</DialogTitle>
                    <DialogDescription>
                        This will close the visit. Make sure all intended work
                        for this encounter is done before proceeding.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3 text-sm">
                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-100">
                        This action is operationally irreversible. The visit
                        will move to completed and drop off the active visits
                        list.
                    </div>

                    {blockingReasons.map((reason) => (
                        <div
                            key={reason}
                            className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-900 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-100"
                        >
                            <AlertTriangle className="mr-2 inline h-4 w-4" />
                            {reason}
                        </div>
                    ))}

                    {warningMessages.map((message) => (
                        <div
                            key={message}
                            className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-100"
                        >
                            <AlertTriangle className="mr-2 inline h-4 w-4" />
                            {message}
                        </div>
                    ))}

                    {!isBlocked && warningMessages.length === 0 ? (
                        <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-100">
                            <CheckCircle2 className="mr-2 inline h-4 w-4" />
                            No pending service or unpaid balance warning is
                            currently recorded for this visit.
                        </div>
                    ) : null}
                </div>

                <Form
                    method="patch"
                    action={`/visits/${visitId}/status`}
                    onSuccess={onSuccess}
                    className="space-y-6"
                >
                    {({ processing, resetAndClearErrors }) => (
                        <>
                            <input
                                type="hidden"
                                name="status"
                                value="completed"
                            />
                            <input
                                type="hidden"
                                name="redirect_to"
                                value={redirectTo}
                            />

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button
                                        variant="secondary"
                                        onClick={() => resetAndClearErrors()}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button
                                    disabled={processing || isBlocked}
                                    type="submit"
                                >
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : null}
                                    Complete Visit
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
