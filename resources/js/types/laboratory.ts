export interface LaboratoryPatientSummary {
    id: string;
    patient_number: string;
    first_name: string;
    last_name: string;
    gender?: string | null;
    age?: number | null;
    age_units?: string | null;
    phone_number?: string | null;
}

export interface LaboratoryVisitSummary {
    id: string;
    visit_number: string;
    patient?: LaboratoryPatientSummary | null;
}

export interface LaboratoryRequestSummary {
    id: string;
    request_date: string;
    priority: string;
    status: string;
    clinical_notes?: string | null;
    requestedBy?: { id: string; first_name: string; last_name: string } | null;
    visit?: LaboratoryVisitSummary | null;
}

export interface LaboratoryConsumableUsage {
    id: string;
    consumable_name: string;
    unit_label: string | null;
    quantity: number;
    unit_cost: number;
    line_cost: number;
    notes: string | null;
    used_at: string;
    recordedBy?: { id: string; first_name: string; last_name: string } | null;
}

export interface LaboratoryResultValue {
    id: string;
    lab_test_result_parameter_id?: string | null;
    label: string;
    value_numeric: number | null;
    value_text: string | null;
    display_value?: string | null;
    unit: string | null;
    gender?: string | null;
    age_min?: number | null;
    age_max?: number | null;
    reference_range: string | null;
}

export interface LaboratoryResultEntry {
    id: string;
    result_notes: string | null;
    review_notes: string | null;
    approval_notes: string | null;
    entered_at: string | null;
    reviewed_at: string | null;
    approved_at: string | null;
    released_at: string | null;
    enteredBy?: { id: string; first_name: string; last_name: string } | null;
    reviewedBy?: { id: string; first_name: string; last_name: string } | null;
    approvedBy?: { id: string; first_name: string; last_name: string } | null;
    entered_by?: { id: string; first_name: string; last_name: string } | null;
    reviewed_by?: { id: string; first_name: string; last_name: string } | null;
    approved_by?: { id: string; first_name: string; last_name: string } | null;
    values?: LaboratoryResultValue[] | null;
}

export interface LaboratorySpecimen {
    id: string;
    accession_number: string;
    specimen_type_id: string;
    specimen_type_name: string;
    status: string;
    collected_at: string | null;
    rejected_at?: string | null;
    rejection_reason?: string | null;
    outside_sample: boolean;
    outside_sample_origin: string | null;
    notes: string | null;
    collectedBy?: { id: string; first_name: string; last_name: string } | null;
    rejectedBy?: { id: string; first_name: string; last_name: string } | null;
}

export interface LaboratoryRequestItem {
    id: string;
    status: string;
    workflow_stage: string;
    result_visible: boolean;
    price: number;
    actual_cost: number;
    costed_at: string | null;
    received_at: string | null;
    result_entered_at: string | null;
    reviewed_at: string | null;
    approved_at: string | null;
    completed_at: string | null;
    specimen?: LaboratorySpecimen | null;
    test?: {
        id: string;
        test_code: string;
        test_name: string;
        category?: string | null;
        specimen_type?: string | null;
        available_specimens?: { id: string; label: string }[] | null;
        result_capture_type?: string | null;
        result_type_name?: string | null;
        description?: string | null;
        base_price?: number | null;
        result_options?: { id?: string; label: string }[] | null;
        result_parameters?:
            | {
                  id?: string;
                  label: string;
                  unit: string | null;
                  gender?: string | null;
                  age_min?: number | null;
                  age_max?: number | null;
                  reference_range: string | null;
                  value_type: 'numeric' | 'text';
              }[]
            | null;
    } | null;
    request?: LaboratoryRequestSummary | null;
    consumables?: LaboratoryConsumableUsage[] | null;
    resultEntry?: LaboratoryResultEntry | null;
    result_entry?: LaboratoryResultEntry | null;
}

export interface PaginatedLaboratoryItemList<T> {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page?: number;
    last_page?: number;
    total?: number;
}

export interface LaboratoryQueueRequest {
    id: string;
    request_date: string;
    priority: string;
    status: string;
    clinical_notes?: string | null;
    billing_status?: string | null;
    requestedBy?: { id: string; first_name: string; last_name: string } | null;
    visit?: LaboratoryVisitSummary | null;
    items: LaboratoryRequestItem[];
}

export interface LaboratoryWorklistPageProps {
    requests: PaginatedLaboratoryItemList<LaboratoryQueueRequest>;
    filters: {
        search: string | null;
        status: string | null;
    };
    statuses: { value: string; label: string }[];
}

export interface LaboratoryRequestItemPageProps {
    labRequestItem: LaboratoryRequestItem;
}

export interface LaboratoryDashboardMetric {
    label: string;
    value: number;
    hint: string;
}

export interface LaboratoryDashboardPageProps {
    metrics: LaboratoryDashboardMetric[];
    request_status_counts: { label: string; value: string; count: number }[];
    workflow_stage_counts: { label: string; value: string; count: number }[];
    recent_requests: LaboratoryQueueRequest[];
}

export interface LaboratoryQueuePageMeta {
    stage: 'incoming' | 'enter_results' | 'review_results' | 'view_results';
    title: string;
    description: string;
    action_label: string;
    route: string;
}

export interface LaboratoryQueuePageProps {
    page: LaboratoryQueuePageMeta;
    requests: PaginatedLaboratoryItemList<LaboratoryQueueRequest>;
    filters: {
        search: string | null;
    };
}
