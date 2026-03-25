export interface LabTestCatalogOption {
    value: string;
    label: string;
    code?: string;
    description?: string | null;
}

export interface LabTestCatalogSpecimenType {
    id: string;
    name: string;
}

export interface LabTestCatalogResultOption {
    id?: string;
    label: string;
    sort_order?: number;
    is_active?: boolean;
}

export interface LabTestCatalogResultParameter {
    id?: string;
    label: string;
    unit: string | null;
    reference_range: string | null;
    value_type: 'numeric' | 'text';
    sort_order?: number;
    is_active?: boolean;
}

export interface LabTestCatalog {
    id: string;
    test_code: string;
    test_name: string;
    lab_test_category_id: string;
    result_type_id: string;
    specimen_type_ids: string[];
    category: string | null;
    specimen_type: string | null;
    specimen_types?: LabTestCatalogSpecimenType[];
    result_capture_type: string | null;
    result_type_name: string | null;
    result_options?: LabTestCatalogResultOption[];
    result_parameters?: LabTestCatalogResultParameter[];
    description: string | null;
    base_price: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaginatedLabTestCatalogList<T> {
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

export interface LabTestCatalogIndexPageProps {
    labTests: PaginatedLabTestCatalogList<LabTestCatalog> | LabTestCatalog[];
    filters: {
        search: string | null;
    };
}

export interface LabTestCatalogFormPageProps {
    categories: LabTestCatalogOption[];
    specimenTypes: LabTestCatalogOption[];
    resultTypes: LabTestCatalogOption[];
}

export interface LabTestCatalogEditPageProps extends LabTestCatalogFormPageProps {
    labTestCatalog: LabTestCatalog;
}
