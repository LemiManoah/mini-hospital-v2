export interface AppointmentClinicOption {
    id: string;
    name: string;
    clinic_name?: string;
}

export interface AppointmentCategory {
    id: string;
    name: string;
    description: string | null;
    clinic_id: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    clinic?: AppointmentClinicOption | null;
}

export interface AppointmentMode {
    id: string;
    name: string;
    description: string | null;
    is_virtual: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface AppointmentPaginatedList<T> {
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

export interface AppointmentCategoryIndexPageProps {
    appointmentCategories:
        | AppointmentPaginatedList<AppointmentCategory>
        | AppointmentCategory[];
    filters: {
        search: string | null;
    };
}

export interface AppointmentCategoryFormPageProps {
    clinics: AppointmentClinicOption[];
}

export interface AppointmentCategoryEditPageProps
    extends AppointmentCategoryFormPageProps {
    appointmentCategory: AppointmentCategory;
}

export interface AppointmentModeIndexPageProps {
    appointmentModes: AppointmentPaginatedList<AppointmentMode> | AppointmentMode[];
    filters: {
        search: string | null;
    };
}

export interface AppointmentModeEditPageProps {
    appointmentMode: AppointmentMode;
}

export interface DoctorSchedule {
    id: string;
    doctor_id: string;
    clinic_id: string;
    facility_branch_id: string | null;
    day_of_week: string;
    start_time: string;
    end_time: string;
    slot_duration_minutes: number;
    max_patients: number;
    valid_from: string;
    valid_to: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    doctor?: { id: string; first_name?: string; last_name?: string; name?: string } | null;
    clinic?: AppointmentClinicOption | null;
    branch?: { id: string; name: string } | null;
}

export interface DoctorScheduleIndexPageProps {
    doctorSchedules: AppointmentPaginatedList<DoctorSchedule> | DoctorSchedule[];
    filters: {
        search: string | null;
    };
}

export interface DoctorScheduleFormPageProps {
    dayOptions: { value: string; label: string }[];
    doctors: { id: string; name: string }[];
    clinics: AppointmentClinicOption[];
}

export interface DoctorScheduleEditPageProps extends DoctorScheduleFormPageProps {
    doctorSchedule: DoctorSchedule;
}

export interface AppointmentPatientOption {
    id: string;
    name: string;
    patient_number: string;
    phone_number: string | null;
    email?: string | null;
}

export interface Appointment {
    id: string;
    patient_id: string;
    doctor_id: string | null;
    clinic_id: string | null;
    appointment_category_id: string | null;
    appointment_mode_id: string | null;
    appointment_date: string;
    start_time: string;
    end_time: string | null;
    status: string;
    reason_for_visit: string;
    chief_complaint: string | null;
    is_walk_in: boolean;
    queue_number: number | null;
    checked_in_at: string | null;
    completed_at: string | null;
    cancellation_reason: string | null;
    rescheduled_from_appointment_id?: string | null;
    notes: string | null;
    patient?: AppointmentPatientOption | null;
    doctor?: { id: string; first_name?: string; last_name?: string; name?: string } | null;
    clinic?: AppointmentClinicOption | null;
    category?: { id: string; name: string } | null;
    mode?: { id: string; name: string; is_virtual?: boolean } | null;
    branch?: { id: string; name: string } | null;
    visit?: { id: string; appointment_id: string; visit_number: string; status: string } | null;
}

export interface AppointmentIndexPageProps {
    appointments: AppointmentPaginatedList<Appointment> | Appointment[];
    filters: {
        search: string | null;
        status: string | null;
        date: string | null;
    };
    statusOptions: { value: string; label: string }[];
}

export interface AppointmentFormPageProps {
    patients: AppointmentPatientOption[];
    doctors: { id: string; name: string }[];
    clinics: AppointmentClinicOption[];
    appointmentCategories: { id: string; name: string }[];
    appointmentModes: { id: string; name: string; is_virtual?: boolean }[];
}

export interface AppointmentShowPageProps extends AppointmentFormPageProps {
    appointment: Appointment;
    statusOptions: { value: string; label: string }[];
    visitTypes: { value: string; label: string }[];
    billingTypes: { value: string; label: string }[];
    insuranceCompanies: { id: string; name: string }[];
    insurancePackages: { id: string; name: string; insurance_company_id: string }[];
}
