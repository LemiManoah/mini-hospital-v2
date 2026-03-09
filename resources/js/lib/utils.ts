import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatIdentifierLabel(value: string): string {
    return value
        .replace(/[._-]+/g, ' ')
        .trim()
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

/**
 * Format a permission name by stripping the group prefix (everything before
 * the first dot) and then running the result through
 * `formatIdentifierLabel`.  For example `countries.view` becomes `View` and
 * `subscription_packages.create` becomes `Create`.
 */
export function formatPermissionLabel(permissionName: string): string {
    const parts = permissionName.split('.');
    // drop the first segment (group) and join the rest back in case there are
    // additional dots (unlikely but safe).
    parts.shift();
    return formatIdentifierLabel(parts.join('.'));
}
