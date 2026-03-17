export type OnboardingStepKey = 'profile' | 'branch' | 'departments' | 'staff';

export type OnboardingStep = {
    key: OnboardingStepKey;
    title: string;
    description: string;
    status: 'complete' | 'current' | 'upcoming';
};

export type OnboardingSelectOption = {
    value: string;
    label: string;
};

export type OnboardingCountryOption = {
    id: string;
    name: string;
};

export type OnboardingCurrencyOption = {
    id: string;
    name: string;
    code: string;
    symbol: string | null;
};

export type OnboardingAddressOption = {
    id: string;
    city: string;
    district: string | null;
    state: string | null;
    country_id: string | null;
};

export type OnboardingDepartmentDraft = {
    name: string;
    location: string;
    is_clinical: boolean;
};

export type OnboardingDepartment = {
    id: string;
    name: string;
    location: string | null;
    is_clinical: boolean;
};

export type OnboardingStaffPosition = {
    id: string;
    name: string;
};

export type OnboardingStaffType = {
    value: string;
    label: string;
};

export type OnboardingTenant = {
    id: string;
    name: string;
    domain: string | null;
    facility_level: string;
    facility_level_label: string;
    country_id: string | null;
    address_id: string | null;
    address: {
        id: string;
        city: string | null;
        district: string | null;
        state: string | null;
    } | null;
};

export type OnboardingBranch = {
    name: string;
    branch_code: string;
    email: string | null;
    main_contact: string | null;
    other_contact: string | null;
    currency_id: string | null;
    has_store: boolean;
    address_id: string | null;
    address: {
        id: string;
        city: string | null;
        district: string | null;
        state: string | null;
        country_id: string | null;
    } | null;
} | null;

export type OnboardingPageProps = {
    tenant: OnboardingTenant;
    currentStep: OnboardingStepKey;
    steps: OnboardingStep[];
    facilityLevels: OnboardingSelectOption[];
    countries: OnboardingCountryOption[];
    currencies: OnboardingCurrencyOption[];
    addresses: OnboardingAddressOption[];
    branch: OnboardingBranch;
    departments: OnboardingDepartment[];
    staffPositions: OnboardingStaffPosition[];
    staffTypes: OnboardingStaffType[];
};

export function formatOnboardingAddress(
    address:
        | Pick<OnboardingAddressOption, 'city' | 'district' | 'state'>
        | null
        | undefined,
): string {
    if (!address) {
        return 'No address selected yet.';
    }

    return `${address.city}${address.district ? `, ${address.district}` : ''}${address.state ? `, ${address.state}` : ''}`;
}

export const defaultOnboardingDepartments = (): OnboardingDepartmentDraft[] => [
    { name: 'Outpatient', location: 'Ground floor', is_clinical: true },
    { name: 'Emergency', location: 'Front wing', is_clinical: true },
    {
        name: 'Laboratory',
        location: 'Diagnostics block',
        is_clinical: true,
    },
];

export const emptyOnboardingDepartment = (): OnboardingDepartmentDraft => ({
    name: '',
    location: '',
    is_clinical: true,
});
