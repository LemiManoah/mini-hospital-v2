import { InsuranceCompany } from './insurance-company';
import { InsurancePackage } from './insurance-package';

export interface VisitCompletionCheck {
    can_complete: boolean;
    has_pending_services: boolean;
    pending_services_count: number;
    has_unpaid_balance: boolean;
    unpaid_balance: number;
    blocking_reasons: string[];
    warning_messages: string[];
}

export interface VitalSign {
    id: string;
    recorded_at: string;
    temperature: number | null;
    temperature_unit: 'celsius' | 'fahrenheit';
    pulse_rate: number | null;
    respiratory_rate: number | null;
    systolic_bp: number | null;
    diastolic_bp: number | null;
    map: number | null;
    oxygen_saturation: number | null;
    on_supplemental_oxygen: boolean;
    oxygen_delivery_method: string | null;
    oxygen_flow_rate: number | null;
    blood_glucose: number | null;
    blood_glucose_unit: 'mg_dl' | 'mmol_l';
    pain_score: number | null;
    height_cm: number | null;
    weight_kg: number | null;
    bmi: number | null;
    head_circumference_cm: number | null;
    chest_circumference_cm: number | null;
    muac_cm: number | null;
    capillary_refill: string | null;
    recordedBy?: { id: string; first_name: string; last_name: string } | null;
    recorded_by?: { id: string; first_name: string; last_name: string } | null;
}

export interface TriageRecord {
    id: string;
    visit_id: string;
    triage_datetime: string;
    triage_grade: string;
    attendance_type: string;
    news_score: number | null;
    pews_score: number | null;
    conscious_level: string;
    mobility_status: string;
    chief_complaint: string;
    history_of_presenting_illness: string | null;
    requires_priority: boolean;
    is_pediatric: boolean;
    poisoning_case: boolean;
    poisoning_agent: string | null;
    snake_bite_case: boolean;
    referred_by: string | null;
    nurse_notes: string | null;
    nurse?: { id: string; first_name: string; last_name: string } | null;
    assignedClinic?: { id: string; name: string } | null;
    assigned_clinic?: { id: string; name: string } | null;
    vitalSigns?: VitalSign[];
    vital_signs?: VitalSign[];
}

export interface Consultation {
    id: string;
    visit_id: string;
    doctor_id: string;
    started_at: string;
    completed_at: string | null;
    chief_complaint: string | null;
    history_of_present_illness: string | null;
    review_of_systems: string | null;
    past_medical_history_summary: string | null;
    family_history: string | null;
    social_history: string | null;
    subjective_notes: string | null;
    objective_findings: string | null;
    assessment: string | null;
    plan: string | null;
    primary_diagnosis: string | null;
    primary_icd10_code: string | null;
    outcome: string | null;
    follow_up_instructions: string | null;
    follow_up_days: number | null;
    is_referred: boolean;
    referred_to_department: string | null;
    referred_to_facility: string | null;
    referral_reason: string | null;
    doctor?: { id: string; first_name: string; last_name: string } | null;
}

export interface ReferralFacilityOption {
    id: string;
    name: string;
    facility_type: string | null;
    phone: string | null;
    email: string | null;
    is_active: boolean;
}

export interface LabTestOption {
    id: string;
    test_code: string;
    test_name: string;
    category: string | null;
    specimen_type?: string | null;
    result_type_name?: string | null;
    result_capture_type?: string | null;
    base_price: number | null;
    quoted_price?: number | null;
    price_source?: string | null;
}

export interface LabResultValue {
    id: string;
    lab_test_result_parameter_id?: string | null;
    label: string;
    value_numeric: number | null;
    value_text: string | null;
    display_value?: string | null;
    unit: string | null;
    reference_range: string | null;
}

export interface LabResultEntry {
    id: string;
    result_notes: string | null;
    review_notes: string | null;
    approval_notes: string | null;
    entered_at: string | null;
    reviewed_at: string | null;
    approved_at: string | null;
    released_at: string | null;
    approvedBy?: { id: string; first_name: string; last_name: string } | null;
    approved_by?: { id: string; first_name: string; last_name: string } | null;
    values?: LabResultValue[] | null;
}

export interface LabRequestItem {
    id: string;
    test_id: string;
    status: string;
    price: number;
    is_external: boolean;
    external_lab_name: string | null;
    workflow_stage?: string;
    result_visible?: boolean;
    completed_at: string | null;
    resultEntry?: LabResultEntry | null;
    result_entry?: LabResultEntry | null;
    test?: LabTestOption | null;
}

export interface LabRequest {
    id: string;
    visit_id: string;
    consultation_id: string | null;
    requested_by: string;
    request_date: string;
    clinical_notes: string | null;
    priority: string;
    status: string;
    diagnosis_code: string | null;
    is_stat: boolean;
    billing_status: string;
    completed_at: string | null;
    requestedBy?: { id: string; first_name: string; last_name: string } | null;
    items: LabRequestItem[];
}

export interface DrugOption {
    id: string;
    generic_name: string;
    brand_name: string | null;
    strength: string | null;
    dosage_form: string | null;
    quoted_price?: number | null;
    price_source?: string | null;
}

export interface PrescriptionItem {
    id: string;
    inventory_item_id: string;
    dosage: string;
    frequency: string;
    route: string;
    duration_days: number;
    quantity: number;
    instructions: string | null;
    is_prn: boolean;
    prn_reason: string | null;
    is_external_pharmacy: boolean;
    status: string;
    dispensed_at: string | null;
    inventory_item?: DrugOption | null;
}

export interface Prescription {
    id: string;
    visit_id: string;
    consultation_id: string | null;
    prescribed_by: string;
    prescription_date: string;
    is_discharge_medication: boolean;
    is_long_term: boolean;
    primary_diagnosis: string | null;
    pharmacy_notes: string | null;
    status: string;
    prescribedBy?: { id: string; first_name: string; last_name: string } | null;
    items: PrescriptionItem[];
}

export interface ImagingRequest {
    id: string;
    visit_id: string;
    consultation_id: string | null;
    requested_by: string;
    modality: string;
    body_part: string;
    laterality: string;
    clinical_history: string;
    indication: string;
    priority: string;
    status: string;
    scheduled_date: string | null;
    requires_contrast: boolean;
    contrast_allergy_status: string | null;
    pregnancy_status: string;
    radiation_dose_msv: number | null;
    requestedBy?: { id: string; first_name: string; last_name: string } | null;
    scheduledBy?: { id: string; first_name: string; last_name: string } | null;
}

export interface FacilityServiceOption {
    id: string;
    service_code: string;
    name: string;
    category: string;
    selling_price?: number | null;
    quoted_price?: number | null;
    price_source?: string | null;
    is_billable: boolean;
}

export interface FacilityServiceOrder {
    id: string;
    visit_id: string;
    consultation_id: string | null;
    facility_service_id: string;
    ordered_by: string;
    status: string;
    ordered_at: string;
    completed_at: string | null;
    service?: FacilityServiceOption | null;
    orderedBy?: { id: string; first_name: string; last_name: string } | null;
    performedBy?: { id: string; first_name: string; last_name: string } | null;
}

export interface VisitPayer {
    id: string;
    patient_visit_id: string;
    billing_type: 'cash' | 'insurance';
    insurance_company_id: string | null;
    insurance_package_id: string | null;
    insurance_company?: Pick<InsuranceCompany, 'id' | 'name'>;
    insurance_package?: Pick<InsurancePackage, 'id' | 'name'>;
    insuranceCompany?: Pick<InsuranceCompany, 'id' | 'name'>;
    insurancePackage?: Pick<InsurancePackage, 'id' | 'name'>;
}

export interface VisitCharge {
    id: string;
    visit_billing_id: string;
    patient_visit_id: string;
    source_type: string;
    source_id: string;
    charge_code: string | null;
    description: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    status: string;
    charged_at: string | null;
}

export interface VisitPayment {
    id: string;
    visit_billing_id: string;
    patient_visit_id: string;
    receipt_number: string | null;
    payment_date: string | null;
    amount: number;
    payment_method: string | null;
    reference_number: string | null;
    is_refund: boolean;
    notes: string | null;
}

export interface VisitBilling {
    id: string;
    patient_visit_id: string;
    visit_payer_id: string;
    payer_type: 'cash' | 'insurance';
    gross_amount: number;
    discount_amount: number;
    paid_amount: number;
    balance_amount: number;
    status: string;
    billed_at: string | null;
    settled_at: string | null;
    payments?: VisitPayment[] | null;
}

export interface Patient {
    id: string;
    patient_number: string;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    date_of_birth: string | null;
    age: number | null;
    age_units: 'year' | 'month' | 'day' | null;
    gender: 'male' | 'female' | 'other' | 'unknown';
    email: string | null;
    phone_number: string;
    alternative_phone: string | null;
    next_of_kin_name: string | null;
    next_of_kin_phone: string | null;
    next_of_kin_relationship: string | null;
    address_id: string | null;
    marital_status: string | null;
    occupation: string | null;
    religion: string | null;
    country_id: string | null;
    blood_group: string | null;
    country?: { id: string; country_name: string };
    address?: { id: string; city: string; district: string | null } | null;
    completed_visits_count?: number;
    last_completed_visit_at?: string | null;
    activeAllergies?: PatientAllergy[];
    allergies?: PatientAllergy[];
}

export interface PaginatedList<T> {
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

export interface VisitFormOptions {
    companies: Pick<InsuranceCompany, 'id' | 'name'>[];
    packages: Pick<InsurancePackage, 'id' | 'name' | 'insurance_company_id'>[];
    clinics: { id: string; name?: string; clinic_name?: string }[];
    doctors: { id: string; first_name: string; last_name: string }[];
    visitTypes: { value: string; label: string }[];
}

export interface PatientIndexPageProps {
    patients: PaginatedList<Patient> | Patient[];
    filters: {
        search: string | null;
    };
}

interface PatientBaseFormProps {
    countries: { id: string; country_name: string }[];
    addresses: { id: string; city: string; district: string | null }[];
    genderOptions: { value: string; label: string }[];
    maritalStatusOptions: { value: string; label: string }[];
    bloodGroupOptions: { value: string; label: string }[];
    religionOptions: { value: string; label: string }[];
    kinRelationshipOptions: { value: string; label: string }[];
}

export interface PatientCreatePageProps
    extends PatientBaseFormProps, VisitFormOptions {}

export interface PatientEditPageProps extends PatientBaseFormProps {
    patient: Patient;
}

export interface PatientAllergy {
    id: string;
    patient_id: string;
    allergen_id: string;
    reaction: string | null;
    severity: string | null;
    is_active: boolean;
    allergen?: { id: string; name: string };
}

export interface PatientVisit {
    id: string;
    visit_number: string;
    visit_type: string;
    status: string;
    is_emergency: boolean;
    notes: string | null;
    registered_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string;
    clinic?: { id: string; name: string } | null;
    doctor?: { id: string; first_name: string; last_name: string } | null;
    branch?: { id: string; name: string } | null;
    registeredBy?: { id: string; name: string } | null;
    registered_by?: { id: string; name: string } | null;
    patient?: Patient | null;
    payer?: VisitPayer | null;
    billing?: VisitBilling | null;
    charges?: VisitCharge[] | null;
    payments?: VisitPayment[] | null;
    triage?: TriageRecord | null;
    consultation?: Consultation | null;
    labRequests?: LabRequest[] | null;
    lab_requests?: LabRequest[] | null;
    imagingRequests?: ImagingRequest[] | null;
    imaging_requests?: ImagingRequest[] | null;
    prescriptions?: Prescription[] | null;
    facilityServiceOrders?: FacilityServiceOrder[] | null;
    facility_service_orders?: FacilityServiceOrder[] | null;
    completion_check?: VisitCompletionCheck | null;
}

export interface PatientStats {
    total_visits: number;
    completed_visits: number;
    emergency_visits: number;
    last_visit: string | null;
}

export interface PatientShowPageProps extends VisitFormOptions {
    patient: Patient & {
        allergies: PatientAllergy[];
        visits: PatientVisit[];
        address?: { id: string; city: string; district: string | null } | null;
    };
    stats: PatientStats;
    hasActiveVisit: boolean;
    allergens: { id: string; name: string; type: string }[];
    severityOptions: { value: string; label: string }[];
    reactionOptions: { value: string; label: string }[];
}

export interface ActiveVisitsPageProps {
    visits: PaginatedList<PatientVisit> | PatientVisit[];
    filters: {
        search: string | null;
    };
}

export interface ReturningPatientsPageProps extends VisitFormOptions {
    patients: PaginatedList<Patient> | Patient[];
    filters: {
        search: string | null;
    };
}

export interface VisitShowPageProps {
    visit: PatientVisit;
    activeTab: string;
    activeClinicalTab: string;
    completionCheck?: VisitCompletionCheck;
    paymentMethods: { value: string; label: string }[];
    triageGrades: { value: string; label: string }[];
    attendanceTypes: { value: string; label: string }[];
    consciousLevels: { value: string; label: string }[];
    mobilityStatuses: { value: string; label: string }[];
    clinics: { id: string; name: string }[];
    temperatureUnits: { value: string; label: string }[];
    bloodGlucoseUnits: { value: string; label: string }[];
    labTestOptions: LabTestOption[];
    drugOptions: DrugOption[];
    labPriorities: { value: string; label: string }[];
    imagingModalities: { value: string; label: string }[];
    imagingPriorities: { value: string; label: string }[];
    imagingLateralities: { value: string; label: string }[];
    pregnancyStatuses: { value: string; label: string }[];
    facilityServiceOptions: FacilityServiceOption[];
    allergens: { id: string; name: string; type: string }[];
    severityOptions: { value: string; label: string }[];
    reactionOptions: { value: string; label: string }[];
}

export interface DoctorConsultationIndexPageProps {
    visits: PaginatedList<PatientVisit> | PatientVisit[];
    filters: {
        search: string | null;
    };
}

export interface TriageQueuePageProps {
    visits: PaginatedList<PatientVisit> | PatientVisit[];
    filters: {
        search: string | null;
    };
}

export interface TriageShowPageProps {
    visit: PatientVisit;
    triageGrades: { value: string; label: string }[];
    attendanceTypes: { value: string; label: string }[];
    consciousLevels: { value: string; label: string }[];
    mobilityStatuses: { value: string; label: string }[];
    clinics: { id: string; name: string }[];
    temperatureUnits: { value: string; label: string }[];
    bloodGlucoseUnits: { value: string; label: string }[];
}

export interface DoctorConsultationShowPageProps {
    visit: PatientVisit;
    activeTab: string;
    consultationOutcomes: { value: string; label: string }[];
    referralDepartmentOptions: { value: string; label: string }[];
    referralFacilityOptions: ReferralFacilityOption[];
    labTestOptions: LabTestOption[];
    drugOptions: DrugOption[];
    labPriorities: { value: string; label: string }[];
    imagingModalities: { value: string; label: string }[];
    imagingPriorities: { value: string; label: string }[];
    imagingLateralities: { value: string; label: string }[];
    pregnancyStatuses: { value: string; label: string }[];
    facilityServiceOptions: FacilityServiceOption[];
    allergens: { id: string; name: string; type: string }[];
    severityOptions: { value: string; label: string }[];
    reactionOptions: { value: string; label: string }[];
}
