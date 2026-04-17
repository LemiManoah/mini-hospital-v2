import type { InventoryNavigationContext } from './inventory-navigation';

export interface PharmacyLocationOption {
    id: string;
    name: string;
    location_code: string;
    is_dispensing_point: boolean;
}

export interface PharmacyQueueStatusOption {
    value: string;
    label: string;
}

export interface PharmacyPatientSummary {
    id: string;
    patient_number: string | null;
    full_name: string;
    gender: string | null;
    phone_number: string | null;
}

export interface PharmacyPrescriberSummary {
    id: string;
    name: string;
}

export interface PharmacyPrescriptionItem {
    id: string;
    inventory_item_id: string;
    item_name: string | null;
    generic_name: string | null;
    brand_name: string | null;
    strength: string | null;
    dosage_form: string | null;
    dosage: string;
    frequency: string;
    route: string;
    duration_days: number;
    quantity: number;
    remaining_quantity: number;
    covered_quantity: number;
    locally_dispensed_quantity: number;
    instructions: string | null;
    status: string | null;
    status_label: string | null;
    dispensed_at?: string | null;
    external_pharmacy: boolean;
    available_quantity?: number;
    stock_status?: string;
    stock_status_label?: string;
}

export interface PharmacyAvailabilitySummary {
    status: string;
    label: string;
    ready_items: number;
    partial_items: number;
    out_of_stock_items: number;
}

export interface PharmacyQueuePrescription {
    id: string;
    visit_id: string;
    visit_number: string | null;
    prescription_date: string | null;
    status: string | null;
    status_label: string | null;
    primary_diagnosis: string | null;
    pharmacy_notes: string | null;
    patient: PharmacyPatientSummary | null;
    prescribed_by: PharmacyPrescriberSummary | null;
    items: PharmacyPrescriptionItem[];
    availability: PharmacyAvailabilitySummary;
    items_count: number;
    pending_items_count: number;
    active_treatment_plan?: PharmacyTreatmentPlanQueueSummary | null;
}

export interface PaginatedPharmacyQueue {
    data: PharmacyQueuePrescription[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface PharmacyQueuePageProps {
    navigation: InventoryNavigationContext;
    prescriptions: PaginatedPharmacyQueue;
    filters: {
        search: string | null;
        status: string | null;
    };
    statusOptions: PharmacyQueueStatusOption[];
    dispensingLocations: PharmacyLocationOption[];
    availableBatchBalances: PharmacyAvailableBatchBalance[];
    pharmacyPolicy: PharmacyPolicy;
}

export interface PharmacyTreatmentPlanQueueSummary {
    id: string;
    status: string | null;
    status_label: string | null;
    next_refill_date: string | null;
    completed_cycles: number;
    total_authorized_cycles: number;
}

export interface PrescriptionDispensingRecordSummary {
    id: string;
    dispense_number: string;
    status: string | null;
    status_label: string | null;
    dispensed_at: string | null;
    inventory_location: {
        id: string;
        name: string;
        location_code: string;
    } | null;
    dispensed_by: string | null;
}

export interface PharmacyPrescriptionDetail {
    id: string;
    visit_id: string;
    visit_number: string | null;
    prescription_date: string | null;
    status: string | null;
    status_label: string | null;
    primary_diagnosis: string | null;
    pharmacy_notes: string | null;
    patient: PharmacyPatientSummary | null;
    prescribed_by: PharmacyPrescriberSummary | null;
    items: PharmacyPrescriptionItem[];
    dispensing_records?: PrescriptionDispensingRecordSummary[];
    treatment_plans?: PharmacyTreatmentPlanSummary[];
}

export interface PharmacyPrescriptionShowPageProps {
    navigation: InventoryNavigationContext;
    prescription: PharmacyPrescriptionDetail;
    dispensingLocations: PharmacyLocationOption[];
}

export interface DispenseCreatePageProps {
    navigation: InventoryNavigationContext;
    prescription: PharmacyPrescriptionDetail;
    dispensingLocations: PharmacyLocationOption[];
    defaults: {
        inventory_location_id: string | null;
        dispensed_at: string;
    };
    pharmacyPolicy: PharmacyPolicy;
}

export interface PharmacyPolicy {
    batch_tracking_enabled: boolean;
    allow_partial_dispense: boolean;
}

export interface PharmacyAvailableBatchBalance {
    inventory_batch_id: string;
    inventory_location_id: string;
    inventory_item_id: string;
    batch_number: string | null;
    expiry_date: string | null;
    quantity: number;
    item_name: string | null;
}

export interface DispensingRecordItemAllocationDetail {
    id: string;
    inventory_batch_id: string;
    quantity: number;
    batch_number_snapshot: string | null;
    expiry_date_snapshot: string | null;
}

export interface DispensingRecordItemDetail {
    id: string;
    prescription_item_id: string;
    inventory_item_id: string;
    prescribed_quantity: number;
    dispensed_quantity: number;
    balance_quantity: number;
    dispense_status: string | null;
    dispense_status_label: string | null;
    external_pharmacy: boolean;
    external_reason: string | null;
    notes: string | null;
    item_name: string | null;
    generic_name: string | null;
    substitution_item_name: string | null;
    allocations: DispensingRecordItemAllocationDetail[];
}

export interface DispensingRecordDetail {
    id: string;
    dispense_number: string;
    status: string | null;
    status_label: string | null;
    dispensed_at: string | null;
    notes: string | null;
    visit_number: string | null;
    patient: Pick<PharmacyPatientSummary, 'id' | 'patient_number' | 'full_name'> | null;
    prescription: {
        id: string;
        status: string | null;
        status_label: string | null;
        primary_diagnosis: string | null;
        pharmacy_notes: string | null;
    } | null;
    inventory_location: {
        id: string;
        name: string;
        location_code: string;
    } | null;
    dispensed_by: string | null;
    items: DispensingRecordItemDetail[];
    can_post: boolean;
}

export interface DispenseShowPageProps {
    navigation: InventoryNavigationContext;
    dispensingRecord: DispensingRecordDetail;
    availableBatchBalances: PharmacyAvailableBatchBalance[];
    pharmacyPolicy: PharmacyPolicy;
}

export interface DispensingHistoryRecord {
    id: string;
    dispense_number: string;
    status: string | null;
    status_label: string | null;
    dispensed_at: string | null;
    visit_number: string | null;
    patient_name: string | null;
    patient_number: string | null;
    inventory_location_name: string | null;
    dispensed_by: string | null;
}

export interface PaginatedDispensingHistory {
    data: DispensingHistoryRecord[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface DispensingHistoryPageProps {
    navigation: InventoryNavigationContext;
    records: PaginatedDispensingHistory;
    filters: {
        search: string | null;
        status: string | null;
        from: string | null;
        to: string | null;
    };
}

export interface PharmacyTreatmentPlanItemSummary {
    id: string;
    item_name: string | null;
    generic_name: string | null;
    quantity_per_cycle: number;
    authorized_total_quantity?: number;
    total_cycles?: number;
    completed_cycles?: number;
    remaining_cycles?: number;
}

export interface PharmacyTreatmentPlanCycleSummary {
    id: string;
    cycle_number: number;
    scheduled_for: string | null;
    status: string | null;
    status_label: string | null;
    completed_at?: string | null;
    state?: string;
    dispensing_record?: {
        id: string;
        dispense_number: string;
    } | null;
}

export interface PharmacyTreatmentPlanSummary {
    id: string;
    status: string | null;
    status_label: string | null;
    start_date?: string | null;
    next_refill_date: string | null;
    frequency_unit?: string | null;
    frequency_unit_label?: string | null;
    frequency_interval?: number;
    total_authorized_cycles: number;
    completed_cycles: number;
}

export interface PaginatedPharmacyTreatmentPlans {
    data: Array<{
        id: string;
        status: string | null;
        status_label: string | null;
        visit_number: string | null;
        patient_name: string | null;
        patient_number: string | null;
        frequency_unit_label: string | null;
        frequency_interval: number;
        total_authorized_cycles: number;
        completed_cycles: number;
        remaining_cycles: number;
        next_refill_date: string | null;
        due_state: string;
        item_names: string[];
    }>;
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface PharmacyTreatmentPlansIndexPageProps {
    navigation: InventoryNavigationContext;
    plans: PaginatedPharmacyTreatmentPlans;
    filters: {
        search: string | null;
        status: string | null;
        due: string | null;
    };
    statusOptions: PharmacyQueueStatusOption[];
    dueOptions: PharmacyQueueStatusOption[];
}

export interface PharmacyTreatmentPlanCreatePageProps {
    navigation: InventoryNavigationContext;
    prescription: {
        id: string;
        visit_number: string | null;
        prescription_date: string | null;
        patient: Pick<PharmacyPatientSummary, 'id' | 'patient_number' | 'full_name'> | null;
        items: Array<{
            id: string;
            inventory_item_id: string;
            item_name: string | null;
            generic_name: string | null;
            dosage: string | null;
            frequency: string | null;
            route: string | null;
            ordered_quantity: number;
            remaining_quantity: number;
        }>;
    };
}

export interface PharmacyTreatmentPlanShowPageProps {
    navigation: InventoryNavigationContext;
    treatmentPlan: {
        id: string;
        status: string | null;
        status_label: string | null;
        visit_number: string | null;
        patient: Pick<PharmacyPatientSummary, 'id' | 'patient_number' | 'full_name'> | null;
        prescribed_by: PharmacyPrescriberSummary | null;
        start_date: string | null;
        frequency_unit: string | null;
        frequency_unit_label: string | null;
        frequency_interval: number;
        total_authorized_cycles: number;
        completed_cycles: number;
        next_refill_date: string | null;
        notes: string | null;
        items: PharmacyTreatmentPlanItemSummary[];
        cycles: PharmacyTreatmentPlanCycleSummary[];
        next_pending_cycle: {
            id: string;
            cycle_number: number;
            scheduled_for: string | null;
            state: string;
        } | null;
    };
}

export interface PharmacyTreatmentPlanCycleDispensePageProps {
    navigation: InventoryNavigationContext;
    treatmentPlan: {
        id: string;
        visit_number: string | null;
        patient: Pick<PharmacyPatientSummary, 'id' | 'patient_number' | 'full_name'> | null;
        cycle: {
            id: string;
            cycle_number: number;
            scheduled_for: string | null;
        };
        items: Array<{
            id: string;
            prescription_item_id: string;
            inventory_item_id: string;
            item_name: string | null;
            generic_name: string | null;
            dosage: string | null;
            frequency: string | null;
            route: string | null;
            instructions: string | null;
            quantity_per_cycle: number;
        }>;
    };
    dispensingLocations: PharmacyLocationOption[];
    availableBatchBalances: PharmacyAvailableBatchBalance[];
    pharmacyPolicy: PharmacyPolicy;
    defaults: {
        inventory_location_id: string | null;
        dispensed_at: string;
    };
}
