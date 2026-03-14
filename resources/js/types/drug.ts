export interface Drug {
    id: string;
    generic_name: string;
    brand_name: string | null;
    drug_code: string;
    category: string;
    dosage_form: string;
    strength: string;
    unit: string;
    manufacturer: string | null;
    is_controlled: boolean;
    schedule_class: string | null;
    therapeutic_classes: string[] | null;
    contraindications: string | null;
    interactions: string | null;
    side_effects: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedDrugList<T> {
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

export interface DrugFormOption {
    value: string;
    label: string;
}

export interface DrugIndexPageProps {
    drugs: PaginatedDrugList<Drug> | Drug[];
    filters: {
        search: string | null;
    };
}

export interface DrugFormPageProps {
    categories: DrugFormOption[];
    dosageForms: DrugFormOption[];
}

export interface DrugEditPageProps extends DrugFormPageProps {
    drug: Drug;
}
