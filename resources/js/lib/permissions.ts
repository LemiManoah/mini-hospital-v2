import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function usePermissions() {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    const hasRole = (role: string): boolean =>
        Array.isArray(user?.roles) && user.roles.includes(role);

    const hasPermission = (permission: string): boolean => {
        if (!user) {
            return false;
        }

        if (hasRole('super_admin') || hasRole('admin')) {
            return true;
        }

        return Boolean(user.can?.[permission]);
    };

    const canAny = (permissions: string[]): boolean =>
        permissions.some(hasPermission);

    const canAll = (permissions: string[]): boolean =>
        permissions.every(hasPermission);

    return {
        user,
        hasRole,
        hasPermission,
        canAny,
        canAll,
    };
}
