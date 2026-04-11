import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { type ReactNode } from 'react';

import { type FacilityManagerTenantSummary } from '../types';

interface FacilityManagerTenantHeaderProps {
    tenant: FacilityManagerTenantSummary;
    title: string;
    description: string;
    actions?: ReactNode;
}

export function FacilityManagerTenantHeader({
    tenant,
    title,
    description,
    actions,
}: FacilityManagerTenantHeaderProps) {
    const onboardingComplete = tenant.onboarding_completed_at !== null;

    return (
        <Card className="border-none shadow-sm ring-1 ring-border/50">
            <CardHeader className="gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div className="space-y-4">
                    <div className="flex items-center gap-3">
                        <div>
                            <CardTitle className="text-2xl tracking-tight">
                                {tenant.name}
                            </CardTitle>
                            <CardDescription className="mt-1">
                                {tenant.domain}.mini-hospital.com
                            </CardDescription>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Badge
                            variant={onboardingComplete ? 'secondary' : 'outline'}
                        >
                            {onboardingComplete
                                ? 'Onboarding Complete'
                                : 'Onboarding Open'}
                        </Badge>
                        <Badge variant="outline">
                            {tenant.current_subscription?.status_label ??
                                'No subscription'}
                        </Badge>
                        {tenant.status ? (
                            <Badge variant="outline">{tenant.status}</Badge>
                        ) : null}
                    </div>

                    <div>
                        <p className="text-sm font-medium">{title}</p>
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    </div>
                </div>

                <div className="flex flex-col items-start gap-3 lg:items-end">
                    {actions}
                    <div className="grid gap-3 text-sm text-muted-foreground sm:grid-cols-2">
                        <div className="rounded-2xl bg-muted/50 px-4 py-3">
                            <p className="text-[11px] tracking-[0.2em] uppercase">
                                Country
                            </p>
                            <p className="mt-1 text-foreground">
                                {tenant.country?.country_name ?? 'Not set'}
                            </p>
                        </div>
                        <div className="rounded-2xl bg-muted/50 px-4 py-3">
                            <p className="text-[11px] tracking-[0.2em] uppercase">
                                Address
                            </p>
                            <p className="mt-1 text-foreground">
                                {tenant.address?.display_name ?? 'Not set'}
                            </p>
                        </div>
                    </div>
                </div>
            </CardHeader>
        </Card>
    );
}
