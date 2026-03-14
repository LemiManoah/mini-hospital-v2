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
    vitalSigns?: VitalSign[];
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
    clinics: { id: string; name: string }[];
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
    maritalStatusOptions: { value: string; label: string }[];
    bloodGroupOptions: { value: string; label: string }[];
    religionOptions: { value: string; label: string }[];
    kinRelationshipOptions: { value: string; label: string }[];
}

export interface PatientCreatePageProps
    extends PatientBaseFormProps,
        VisitFormOptions {}

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
    patient?: Patient | null;
    payer?: VisitPayer | null;
    triage?: TriageRecord | null;
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
    allergens: { id: string; name: string }[];
    severityOptions: { value: string; label: string }[];
    reactionOptions: { value: string; label: string }[];
    hasActiveVisit: boolean;
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
    availableTransitions: { value: string; label: string }[];
    completionCheck?: VisitCompletionCheck;
    triageGrades: { value: string; label: string }[];
    attendanceTypes: { value: string; label: string }[];
    consciousLevels: { value: string; label: string }[];
    mobilityStatuses: { value: string; label: string }[];
    clinics: { id: string; name: string }[];
    temperatureUnits: { value: string; label: string }[];
    bloodGlucoseUnits: { value: string; label: string }[];
}
