export interface FacilityManagerTenantSummary {
    id: string;
    name: string;
    domain: string;
    status: string | null;
    facility_level: string | null;
    onboarding_completed_at: string | null;
    address?: {
        display_name: string;
    } | null;
    country?: {
        country_name?: string;
    } | null;
    current_subscription?: {
        id: string;
        status: string;
        status_label: string;
        trial_ends_at: string | null;
        activated_at: string | null;
        current_period_ends_at: string | null;
        package?: {
            name: string;
            price?: string;
        } | null;
        created_at?: string | null;
    } | null;
}

export interface FacilityHealthCheck {
    key: string;
    label: string;
    status: string;
    status_label: string;
    detail: string;
    recommendation: string;
}

export interface FacilityHealthSummary {
    status: string;
    status_label: string;
    summary: {
        total_checks: number;
        passed: number;
        warnings: number;
        critical: number;
    };
    checks: FacilityHealthCheck[];
    recommendations: string[];
}

export interface FacilityManagerMetric {
    label: string;
    value: number;
    hint: string;
}

export interface PaginationLinkItem {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedFacilityManagerList<T> {
    data: T[];
    links: PaginationLinkItem[];
    prev_page_url: string | null;
    next_page_url: string | null;
}
