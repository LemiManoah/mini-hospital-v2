import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { usePermissions } from '@/lib/permissions';
import { Form } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

import { type FacilityManagerTenantSummary } from '../types';

interface FacilityManagerSupportActionsProps {
    tenant: FacilityManagerTenantSummary;
}

export function FacilityManagerSupportActions({
    tenant,
}: FacilityManagerSupportActionsProps) {
    const { hasPermission } = usePermissions();

    if (!hasPermission('tenants.update')) {
        return null;
    }

    const hasSubscription = tenant.current_subscription !== null;

    return (
        <Card className="border-none shadow-sm ring-1 ring-border/50">
            <CardHeader>
                <CardTitle>Support Actions</CardTitle>
                <CardDescription>
                    Workspace switching, billing intervention, and onboarding
                    controls for this facility.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
                <Form
                    method="post"
                    action={`/facility-manager/facilities/${tenant.id}/switch`}
                    className="w-full"
                >
                    {() => (
                        <Button className="w-full">
                            <ShieldCheck className="h-4 w-4" />
                            Switch Into Tenant
                        </Button>
                    )}
                </Form>

                <Form
                    method="post"
                    action={`/facility-manager/facilities/${tenant.id}/activate-subscription`}
                    className="w-full"
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            disabled={processing || !hasSubscription}
                            className="w-full"
                        >
                            Activate Subscription
                        </Button>
                    )}
                </Form>

                <Form
                    method="post"
                    action={`/facility-manager/facilities/${tenant.id}/mark-subscription-past-due`}
                    className="w-full"
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            disabled={processing || !hasSubscription}
                            variant="outline"
                            className="w-full"
                        >
                            Mark Subscription Past Due
                        </Button>
                    )}
                </Form>

                {tenant.onboarding_completed_at ? (
                    <Form
                        method="post"
                        action={`/facility-manager/facilities/${tenant.id}/reopen-onboarding`}
                        className="w-full"
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                disabled={processing}
                                variant="outline"
                                className="w-full"
                            >
                                Reopen Onboarding
                            </Button>
                        )}
                    </Form>
                ) : (
                    <Form
                        method="post"
                        action={`/facility-manager/facilities/${tenant.id}/complete-onboarding`}
                        className="w-full"
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                disabled={processing}
                                variant="secondary"
                                className="w-full"
                            >
                                Mark Onboarding Complete
                            </Button>
                        )}
                    </Form>
                )}

                {!hasSubscription ? (
                    <p className="text-sm text-muted-foreground">
                        Subscription actions stay disabled until this tenant has
                        a current subscription record.
                    </p>
                ) : null}
            </CardContent>
        </Card>
    );
}
