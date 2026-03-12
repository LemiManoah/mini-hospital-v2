import { InsuranceCompany } from './insurance-company';
import { InsurancePackage } from './insurance-package';

export interface PatientInsurance {
    id: string;
    patient_id: string;
    insurance_company_id: string;
    insurance_package_id: string;
    insurance_company?: Pick<InsuranceCompany, 'id' | 'name'>;
    insurance_package?: Pick<InsurancePackage, 'id' | 'name'>;
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
    default_payer_type: 'cash' | 'insurance';
    country?: { id: string; country_name: string };
    primary_insurance?: PatientInsurance | null;
    primaryInsurance?: PatientInsurance | null;
}

export interface PatientIndexPageProps {
    patients:
        | {
              data: Patient[];
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
        | Patient[];
    filters: {
        search: string | null;
    };
}

export interface PatientFormPageProps {
    countries: { id: string; country_name: string }[];
    addresses: { id: string; city: string; district: string | null }[];
    companies: Pick<InsuranceCompany, 'id' | 'name'>[];
    packages: Pick<InsurancePackage, 'id' | 'name' | 'insurance_company_id'>[];
}

export interface PatientCreatePageProps extends PatientFormPageProps {}

export interface PatientEditPageProps extends PatientFormPageProps {
    patient: Patient;
}
