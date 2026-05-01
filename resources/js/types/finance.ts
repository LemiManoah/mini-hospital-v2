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
    discount_amount?: number | null;
    paid_amount?: number | null;
    balance_amount?: number | null;
    status?: string | null;
    payments?: VisitPayment[] | null;
    discounts?: BillingDiscount[] | null;
}

export interface BillingDiscount {
    id: string;
    amount: number;
    reason: string;
    status: 'pending' | 'approved' | 'reversed';
    notes?: string | null;
    requested_at?: string | null;
    approved_at?: string | null;
    reversed_at?: string | null;
    reversal_reason?: string | null;
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

export interface InsuranceInvoiceRow {
    id: string;
    code: string;
    insurance_company_name?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    bill_amount: number;
    paid_amount: number;
    balance_amount: number;
    status: string;
    claims_count: number;
    created_at?: string | null;
}

export interface ReadyInsuranceClaimBatch {
    insurance_company_id: string;
    insurance_company_name: string;
    claims_count: number;
    claim_total: number;
}

export interface FinanceInsuranceInvoicesIndexPageProps {
    invoices: PaginatedList<InsuranceInvoiceRow>;
    readyClaimBatches: ReadyInsuranceClaimBatch[];
    filters: {
        status: string | null;
    };
    statusOptions: { value: string; label: string }[];
}

export interface InsuranceInvoiceClaim {
    id: string;
    claim_reference: string;
    visit_number?: string | null;
    patient_name: string;
    patient_number?: string | null;
    claimed_amount: number;
    approved_amount: number;
    rejected_amount: number;
    copay_amount: number;
    payable_amount: number;
    paid_amount: number;
    outstanding_amount: number;
    status: string;
    invoiced_at?: string | null;
    paid_at?: string | null;
}

export interface InsuranceInvoicePaymentAllocation {
    id: string;
    claim_reference?: string | null;
    allocated_amount: number;
}

export interface InsuranceInvoicePayment {
    id: string;
    payment_date?: string | null;
    receipt?: string | null;
    paid_amount: number;
    allocations: InsuranceInvoicePaymentAllocation[];
}

export interface InsuranceInvoiceDetail extends InsuranceInvoiceRow {
    claims: InsuranceInvoiceClaim[];
    payments: InsuranceInvoicePayment[];
}

export interface FinanceInsuranceInvoicesShowPageProps {
    invoice: InsuranceInvoiceDetail;
    audit_activity: AuditTimelineEntry[];
}
