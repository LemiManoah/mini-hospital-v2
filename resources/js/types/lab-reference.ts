export interface LabLookupRecord {
    id: string;
    tenant_id: string | null;
    name: string;
    description: string | null;
    is_active: boolean;
    code?: string | null;
}

export interface PaginatedLabLookupList<T> {
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

export interface LabLookupIndexPageProps<T = LabLookupRecord> {
    filters: {
        search: string | null;
    };
}

export interface LabTestCategoryIndexPageProps
    extends LabLookupIndexPageProps {
    categories: PaginatedLabLookupList<LabLookupRecord> | LabLookupRecord[];
}

export interface SpecimenTypeIndexPageProps extends LabLookupIndexPageProps {
    specimenTypes: PaginatedLabLookupList<LabLookupRecord> | LabLookupRecord[];
}

export interface ResultTypeIndexPageProps extends LabLookupIndexPageProps {
    resultTypes: PaginatedLabLookupList<LabLookupRecord> | LabLookupRecord[];
}

export interface LabTestCategoryEditPageProps {
    category: LabLookupRecord;
}

export interface SpecimenTypeEditPageProps {
    specimenType: LabLookupRecord;
}

export interface ResultTypeEditPageProps {
    resultType: LabLookupRecord;
}
