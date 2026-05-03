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

export interface InsurancePackagePrice {
    id: string;
    facility_branch_id: string;
    billable_type: BillableItemType;
    billable_id: string;
    billable_name: string;
    price: string;
    effective_from: string | null;
    effective_to: string | null;
    status: 'active' | 'inactive';
    branch: { id: string; name: string } | null;
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
    prices: InsurancePackagePrice[];
    billableItems: {
        service: BillableItemOption[];
        drug: BillableItemOption[];
        test: BillableItemOption[];
    };
    branches: BillableItemOption[];
}
