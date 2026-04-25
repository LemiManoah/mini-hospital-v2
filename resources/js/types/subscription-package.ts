export interface SubscriptionPackage {
    id: string;
    name: string;
    users: number;
    price: string;
    status: 'active' | 'inactive' | 'suspended' | 'cancelled' | 'pending';
    created_at: string;
    updated_at: string;
}

export interface PaginatedSubscriptionPackages {
    data: SubscriptionPackage[];
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
}

export interface SubscriptionPackageIndexPageProps {
    packages: SubscriptionPackage[] | PaginatedSubscriptionPackages;
    filters: {
        search: string | null;
    };
}
