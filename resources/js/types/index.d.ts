import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    auth: Auth;
    flash?: {
        success?: string | null;
        error?: string | null;
        info?: string | null;
        warning?: string | null;
        reconciliationPrompt?: string | null;
    };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface SharedSubscriptionPackage {
    id: string;
    name: string;
    price: string;
}

export interface SharedCurrentSubscription {
    id: string;
    status: string;
    trial_ends_at: string | null;
    subscription_package: SharedSubscriptionPackage | null;
}

export interface SharedTenant {
    id: string;
    name: string;
    current_subscription: SharedCurrentSubscription | null;
}

export interface SharedActiveBranch {
    id: string;
    name: string;
    branch_code: string;
}

export interface User {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    is_support?: boolean;
    roles: string[];
    can: Record<string, boolean>;
    tenant?: SharedTenant | null;
    active_branch?: SharedActiveBranch | null;
    [key: string]: unknown; // This allows for additional properties...
}
