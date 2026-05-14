export interface ChargeMaster {
    id: string;
    tenant_id: string;
    facility_branch_id: string | null;
    item_code: string;
    description: string;
    billable_type: string | null;
    billable_id: string | null;
    unit_price: number | string;
    is_active: boolean;
    effective_from: string | null;
    effective_to: string | null;
}

export interface PaginatedChargeMasterList<T> {
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

export interface ChargeMasterIndexPageProps {
    chargeMasters: PaginatedChargeMasterList<ChargeMaster> | ChargeMaster[];
    filters: {
        search: string | null;
        type: string | null;
    };
    billableTypeOptions: { value: string; label: string }[];
}

export interface ChargeMasterEditPageProps {
    chargeMaster: ChargeMaster;
}
