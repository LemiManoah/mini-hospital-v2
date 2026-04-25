import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

type FacilityManagerTab =
    | 'overview'
    | 'audit'
    | 'branches'
    | 'users'
    | 'subscriptions'
    | 'activity'
    | 'notes';

interface FacilityManagerNavProps {
    tenantId: string;
    current: FacilityManagerTab;
}

const tabs: Array<{
    key: FacilityManagerTab;
    label: string;
    href: (tenantId: string) => string;
}> = [
    {
        key: 'overview',
        label: 'Overview',
        href: (tenantId) => `/facility-manager/facilities/${tenantId}`,
    },
    {
        key: 'audit',
        label: 'Audit',
        href: (tenantId) => `/facility-manager/facilities/${tenantId}/audit`,
    },
    {
        key: 'branches',
        label: 'Branches',
        href: (tenantId) => `/facility-manager/facilities/${tenantId}/branches`,
    },
    {
        key: 'users',
        label: 'Users',
        href: (tenantId) => `/facility-manager/facilities/${tenantId}/users`,
    },
    {
        key: 'subscriptions',
        label: 'Subscriptions',
        href: (tenantId) =>
            `/facility-manager/facilities/${tenantId}/subscriptions`,
    },
    {
        key: 'activity',
        label: 'Activity',
        href: (tenantId) => `/facility-manager/facilities/${tenantId}/activity`,
    },
    {
        key: 'notes',
        label: 'Support Notes',
        href: (tenantId) =>
            `/facility-manager/facilities/${tenantId}/support-notes`,
    },
];

export function FacilityManagerNav({
    tenantId,
    current,
}: FacilityManagerNavProps) {
    return (
        <div className="flex flex-wrap gap-2">
            {tabs.map((tab) => (
                <Button
                    key={tab.key}
                    asChild
                    size="sm"
                    variant={tab.key === current ? 'default' : 'outline'}
                    className={cn(tab.key === current ? 'shadow-sm' : '')}
                >
                    <Link href={tab.href(tenantId)}>{tab.label}</Link>
                </Button>
            ))}
        </div>
    );
}
