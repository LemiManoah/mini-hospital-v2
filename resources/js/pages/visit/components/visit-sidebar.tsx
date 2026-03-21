import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type VisitCompletionCheck } from '@/types/patient';
import { Form } from '@inertiajs/react';
import { Activity, CreditCard } from 'lucide-react';
import { formatMoney } from './visit-show-utils';

type VisitSidebarProps = {
    visit: {
        id: string;
        payer?: {
            billing_type: 'cash' | 'insurance';
            insuranceCompany?: { name?: string | null } | null;
            insurancePackage?: { name?: string | null } | null;
            insurance_company?: { name?: string | null } | null;
            insurance_package?: { name?: string | null } | null;
        } | null;
    };
    completionCheck?: VisitCompletionCheck;
    canUpdateVisit: boolean;
    availableTransitions: { value: string; label: string }[];
};

export function VisitSidebar({
    visit,
    completionCheck,
    canUpdateVisit,
    availableTransitions,
}: VisitSidebarProps) {
    const insurer =
        visit.payer?.insuranceCompany?.name ??
        visit.payer?.insurance_company?.name;
    const packageName =
        visit.payer?.insurancePackage?.name ??
        visit.payer?.insurance_package?.name;

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Payer Snapshot</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                            <CreditCard className="h-5 w-5" />
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Billing Type
                            </p>
                            <p className="font-medium capitalize">
                                {visit.payer?.billing_type ?? 'cash'}
                            </p>
                        </div>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Insurer
                        </p>
                        <p className="font-medium">
                            {insurer || 'Not applicable'}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Package
                        </p>
                        <p className="font-medium">
                            {packageName || 'Not applicable'}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Workflow Snapshot</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                    {completionCheck?.has_pending_services ? (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                            Pending services: {completionCheck.pending_services_count}
                        </div>
                    ) : null}
                    {completionCheck?.has_unpaid_balance ? (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100">
                            Unpaid balance: {formatMoney(completionCheck.unpaid_balance)}
                        </div>
                    ) : null}
                    {completionCheck &&
                    !completionCheck.has_pending_services &&
                    !completionCheck.has_unpaid_balance ? (
                        <p className="text-muted-foreground">
                            This visit has no pending service or billing warnings.
                        </p>
                    ) : null}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {canUpdateVisit && availableTransitions.length > 0 ? (
                        availableTransitions.map((transition) => (
                            <Form
                                key={transition.value}
                                method="patch"
                                action={`/visits/${visit.id}/status`}
                            >
                                <input
                                    type="hidden"
                                    name="status"
                                    value={transition.value}
                                />
                                <Button
                                    type="submit"
                                    className="w-full justify-start"
                                    variant={
                                        transition.value === 'cancelled'
                                            ? 'destructive'
                                            : 'default'
                                    }
                                >
                                    <Activity className="mr-2 h-4 w-4" />
                                    {transition.label}
                                </Button>
                            </Form>
                        ))
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            {canUpdateVisit
                                ? 'No further status actions are available for this visit.'
                                : 'You can review this visit, but you do not have permission to change its workflow status.'}
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
