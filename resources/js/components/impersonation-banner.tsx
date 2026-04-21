import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';

export function ImpersonationBanner() {
    const { impersonation } = usePage<SharedData>().props;

    if (!impersonation?.active) {
        return null;
    }

    return (
        <div className="border-b border-amber-200 bg-amber-50">
            <div className="flex flex-col gap-3 px-4 py-3 md:max-w-7xl md:flex-row md:items-center md:justify-between">
                <div className="flex items-start gap-3">
                    <div className="rounded-full bg-amber-100 p-2 text-amber-700">
                        <ShieldAlert className="h-4 w-4" />
                    </div>
                    <div className="space-y-1">
                        <p className="text-sm font-semibold text-amber-900">
                            You are acting as {impersonation.target_user.name}
                        </p>
                        <p className="text-sm text-amber-800">
                            {impersonation.target_user.email}
                            {impersonation.target_user.tenant_name
                                ? ` in ${impersonation.target_user.tenant_name}`
                                : ''}
                            . Support account: {impersonation.real_user.name}
                        </p>
                    </div>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    className="border-amber-300 bg-white text-amber-900 hover:bg-amber-100"
                    onClick={() =>
                        router.post('/facility-manager/impersonation/stop')
                    }
                >
                    Stop Impersonation
                </Button>
            </div>
        </div>
    );
}
