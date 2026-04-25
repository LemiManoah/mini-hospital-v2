export interface ReferralFacility {
    id: string;
    name: string;
    facility_type: string | null;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedReferralFacilityList {
    data: ReferralFacility[];
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

export interface ReferralFacilityIndexPageProps {
    referralFacilities: PaginatedReferralFacilityList;
    filters: {
        search: string | null;
    };
}

export interface ReferralFacilityEditPageProps {
    referralFacility: ReferralFacility;
}
