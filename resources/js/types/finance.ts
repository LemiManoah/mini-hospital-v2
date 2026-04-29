import type { AuditTimelineEntry } from './audit';
import type { PaginatedList, VisitCharge, VisitPayment } from './patient';

export interface FinanceQueueVisit {
    id: string;
    visit_number: string;
    visit_type: string;
    status: string;
    registered_at: string | null;
    patient: {
        id: string;
        patient_number: string | null;
        full_name: string;
        phone_number: string | null;
    } | null;
    payer: {
        billing_type: 'cash' | 'insurance';
        insurance_company_name?: string | null;
        insurance_package_name?: string | null;
    } | null;
    billing: {
        gross_amount: number;
        paid_amount: number;
        balance_amount: number;
        status: string;
    } | null;
    charges_count: number;
}

export interface FinanceOpdPaymentsIndexPageProps {
    visits: PaginatedList<FinanceQueueVisit>;
    filters: {
        search: string | null;
        payer_type: string | null;
        status: string | null;
    };
    payerTypeOptions: { value: string; label: string }[];
    statusOptions: { value: string; label: string }[];
}

export interface FinanceOpdVisitBilling {
    gross_amount?: number | null;
    paid_amount?: number | null;
    balance_amount?: number | null;
    status?: string | null;
    payments?: VisitPayment[] | null;
}

export interface FinanceOpdPaymentsShowPageProps {
    visit: {
        id: string;
        visit_number: string;
        visit_type: string;
        status: string;
        registered_at?: string | null;
        patient?: {
            first_name: string;
            middle_name?: string | null;
            last_name: string;
            patient_number?: string | null;
            phone_number?: string | null;
            gender?: string | null;
        } | null;
        payer?: {
            billing_type: 'cash' | 'insurance';
            insuranceCompany?: { name?: string | null } | null;
            insurancePackage?: { name?: string | null } | null;
            insurance_company?: { name?: string | null } | null;
            insurance_package?: { name?: string | null } | null;
        } | null;
        billing?: FinanceOpdVisitBilling | null;
        charges?: VisitCharge[] | null;
    };
    paymentMethods: { value: string; label: string }[];
    audit_activity: AuditTimelineEntry[];
}
