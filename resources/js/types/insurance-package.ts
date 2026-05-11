import { type DataImportSummary, type ImportResult } from './data-upload';
import { type InsuranceCompany } from './insurance-company';

export interface InsurancePackage {
    id: string;
    tenant_id: string;
    insurance_company_id: string;
    name: string;
    status: 'active' | 'inactive' | 'suspended' | 'cancelled' | 'pending';
    created_at: string;
    updated_at: string;
    insurance_company?: Pick<InsuranceCompany, 'id' | 'name'>;
}

export type BillableItemType =
    | 'service'
    | 'drug'
    | 'test'
    | 'imaging'
    | 'procedure'
    | 'bed_day'
    | 'other';

export type InsurancePolicyType = 'pharmacy' | 'lab' | 'services';
export type InsuranceCopayType = 'none' | 'fixed' | 'percentage';

export interface InsurancePolicyItem {
    id: string;
    itemType: BillableItemType;
    itemId: string;
    itemName: string;
    price: string;
    copayType: InsuranceCopayType;
    copayTypeLabel: string;
    copayValue: string;
    effectiveFrom: string | null;
    effectiveTo: string | null;
    status: 'active' | 'inactive';
}

export interface InsurancePolicy {
    id: string;
    name: string;
    policyType: InsurancePolicyType;
    policyTypeLabel: string;
    facilityBranchId: string;
    effectiveFrom: string | null;
    effectiveTo: string | null;
    status: 'active' | 'inactive';
    branch: { id: string; name: string; branchCode: string } | null;
    items: InsurancePolicyItem[];
}

export interface BillableItemOption {
    value: string;
    label: string;
}

export interface InsurancePackageIndexPageProps {
    insurancePackages:
        | {
              data: InsurancePackage[];
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
        | InsurancePackage[];
    filters: {
        search: string | null;
    };
}

export interface InsurancePackageCreatePageProps {
    companies: Pick<InsuranceCompany, 'id' | 'name'>[];
}

export interface InsurancePackageEditPageProps extends InsurancePackageCreatePageProps {
    insurancePackage: InsurancePackage;
}

export interface InsurancePackageShowPageProps {
    insurancePackage: InsurancePackage;
    policies: InsurancePolicy[];
    activeBranch: { id: string; name: string; branchCode: string } | null;
    billableItems: {
        service: BillableItemOption[];
        drug: BillableItemOption[];
        test: BillableItemOption[];
    };
    branches: BillableItemOption[];
    policyImports: DataImportSummary[];
    importResult: ImportResult | null;
    importResultMode: 'import' | 'preview';
    queuedImportMessage: string | null;
    selectedPolicyId: string | null;
}
