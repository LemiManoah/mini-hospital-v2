export interface ConsultationTariffFacilityServiceOption {
    value: string;
    label: string;
}

export interface ConsultationTariff {
    id: string;
    visit_type: string | null;
    consultation_type: string;
    facility_service_id: string;
    is_active: boolean;
    facility_service?: {
        id: string;
        name: string;
        service_code: string;
        selling_price: number | null;
    } | null;
}

export interface PaginatedConsultationTariffList<T> {
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

export interface ConsultationTariffFormPageProps {
    visitTypeOptions: { value: string; label: string }[];
    consultationTypeOptions: { value: string; label: string }[];
    facilityServiceOptions: ConsultationTariffFacilityServiceOption[];
}

export interface ConsultationTariffIndexPageProps {
    consultationTariffs:
        | PaginatedConsultationTariffList<ConsultationTariff>
        | ConsultationTariff[];
    filters: {
        search: string | null;
    };
}

export interface ConsultationTariffEditPageProps extends ConsultationTariffFormPageProps {
    consultationTariff: ConsultationTariff;
}
