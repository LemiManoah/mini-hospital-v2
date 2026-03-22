export interface FacilityService {
    id: string;
    service_code: string;
    name: string;
    category: string;
    description: string | null;
    cost_price: number | null;
    selling_price: number | null;
    is_billable: boolean;
    charge_master_id: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedFacilityServiceList<T> {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
}

export interface FacilityServiceFormOption {
    value: string;
    label: string;
}

export interface FacilityServiceIndexPageProps {
    facilityServices:
        | PaginatedFacilityServiceList<FacilityService>
        | FacilityService[];
    filters: {
        search: string | null;
    };
}

export interface FacilityServiceFormPageProps {
    categories: FacilityServiceFormOption[];
}

export interface FacilityServiceEditPageProps extends FacilityServiceFormPageProps {
    facilityService: FacilityService;
}
