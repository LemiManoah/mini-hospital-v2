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
