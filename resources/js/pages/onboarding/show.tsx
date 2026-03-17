import { Button } from '@/components/ui/button';
import OnboardingLayout from '@/layouts/auth/onboarding-layout';
import { BranchStep } from '@/pages/onboarding/components/branch-step';
import { DepartmentsStep } from '@/pages/onboarding/components/departments-step';
import { ProfileStep } from '@/pages/onboarding/components/profile-step';
import { StaffStep } from '@/pages/onboarding/components/staff-step';
import {
    defaultOnboardingDepartments,
    type OnboardingPageProps,
} from '@/types/onboarding';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

export default function OnboardingShow({
    tenant,
    currentStep,
    steps,
    facilityLevels,
    countries,
    currencies,
    addresses,
    branch,
    departments,
    staffPositions,
    staffTypes,
}: OnboardingPageProps) {
    const [selectedFacilityLevel, setSelectedFacilityLevel] = useState(
        tenant.facility_level,
    );
    const [selectedCountryId, setSelectedCountryId] = useState(
        tenant.country_id ?? '',
    );
    const [selectedAddressId, setSelectedAddressId] = useState(
        tenant.address_id ?? '',
    );
    const [selectedBranchCountryId, setSelectedBranchCountryId] = useState(
        branch?.address?.country_id ?? tenant.country_id ?? '',
    );
    const [selectedBranchAddressId, setSelectedBranchAddressId] = useState(
        branch?.address_id ?? tenant.address_id ?? '',
    );
    const [selectedCurrencyId, setSelectedCurrencyId] = useState(
        branch?.currency_id ?? currencies[0]?.id ?? '',
    );
    const [hasStore, setHasStore] = useState(branch?.has_store ?? false);
    const [departmentRows, setDepartmentRows] = useState(
        departments.length > 0
            ? departments.map((department) => ({
                  name: department.name,
                  location: department.location ?? '',
                  is_clinical: department.is_clinical,
              }))
            : defaultOnboardingDepartments(),
    );
    const [selectedStaffPositionId, setSelectedStaffPositionId] = useState(
        staffPositions[0]?.id ?? '',
    );
    const [selectedStaffType, setSelectedStaffType] = useState(
        staffTypes[0]?.value ?? '',
    );
    const [selectedDepartmentIds, setSelectedDepartmentIds] = useState(
        departments.slice(0, 1).map((department) => department.id),
    );

    const selectedCurrency = useMemo(
        () => currencies.find((currency) => currency.id === selectedCurrencyId),
        [currencies, selectedCurrencyId],
    );
    const selectedProfileAddress = useMemo(
        () => addresses.find((address) => address.id === selectedAddressId),
        [addresses, selectedAddressId],
    );
    const selectedBranchAddress = useMemo(
        () =>
            addresses.find((address) => address.id === selectedBranchAddressId),
        [addresses, selectedBranchAddressId],
    );
    const filteredProfileAddresses = useMemo(
        () =>
            selectedCountryId
                ? addresses.filter(
                      (address) => address.country_id === selectedCountryId,
                  )
                : addresses,
        [addresses, selectedCountryId],
    );
    const filteredBranchAddresses = useMemo(
        () =>
            selectedBranchCountryId
                ? addresses.filter(
                      (address) =>
                          address.country_id === selectedBranchCountryId,
                  )
                : addresses,
        [addresses, selectedBranchCountryId],
    );

    useEffect(() => {
        if (
            selectedAddressId &&
            selectedProfileAddress?.country_id !== selectedCountryId
        ) {
            setSelectedAddressId('');
        }
    }, [selectedAddressId, selectedCountryId, selectedProfileAddress]);

    useEffect(() => {
        if (
            selectedBranchAddressId &&
            selectedBranchAddress?.country_id !== selectedBranchCountryId
        ) {
            setSelectedBranchAddressId('');
        }
    }, [
        selectedBranchAddress,
        selectedBranchAddressId,
        selectedBranchCountryId,
    ]);

    const stepIndex = steps.findIndex((step) => step.key === currentStep);
    const previousStep = stepIndex > 0 ? steps[stepIndex - 1] : null;
    const currentStepMeta = steps.find((step) => step.key === currentStep);
    const asideNote =
        currentStep === 'profile'
            ? 'Start by confirming the workspace identity and location details.'
            : currentStep === 'branch'
              ? 'Keep this focused on the first operating branch. The owner will be attached automatically.'
              : currentStep === 'departments'
                ? 'Finish with the first departments so the workspace can open cleanly.'
                : 'Create one operational staff member to finish the workspace setup.';

    return (
        <OnboardingLayout
            title={currentStepMeta?.title ?? 'Onboarding'}
            description={
                currentStepMeta?.description ??
                'Complete the next setup step to continue.'
            }
            tenantName={tenant.name}
            steps={steps}
            currentStep={currentStep}
            asideNote={asideNote}
        >
            <Head title="Onboarding" />

            <div className="mb-6 flex items-center justify-between gap-3">
                {previousStep ? (
                    <Button
                        type="button"
                        variant="ghost"
                        className="px-0 text-zinc-600 hover:bg-transparent hover:text-zinc-950"
                        onClick={() => window.history.back()}
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to {previousStep.title}
                    </Button>
                ) : (
                    <Button
                        variant="ghost"
                        asChild
                        className="px-0 text-zinc-600 hover:bg-transparent hover:text-zinc-950"
                    >
                        <Link href="/create-workspace">
                            <ArrowLeft className="h-4 w-4" />
                            Back to workspace signup
                        </Link>
                    </Button>
                )}

                <div className="text-sm text-zinc-500">
                    Step {stepIndex + 1} of {steps.length}
                </div>
            </div>

            {currentStep === 'profile' ? (
                <ProfileStep
                    tenant={tenant}
                    facilityLevels={facilityLevels}
                    countries={countries}
                    filteredAddresses={filteredProfileAddresses}
                    selectedCountryId={selectedCountryId}
                    selectedAddressId={selectedAddressId}
                    selectedFacilityLevel={selectedFacilityLevel}
                    selectedAddress={selectedProfileAddress}
                    onCountryChange={setSelectedCountryId}
                    onAddressChange={setSelectedAddressId}
                    onFacilityLevelChange={setSelectedFacilityLevel}
                />
            ) : null}

            {currentStep === 'branch' ? (
                <BranchStep
                    tenant={tenant}
                    branch={branch}
                    countries={countries}
                    currencies={currencies}
                    filteredAddresses={filteredBranchAddresses}
                    selectedCountryId={selectedBranchCountryId}
                    selectedAddressId={selectedBranchAddressId}
                    selectedCurrencyId={selectedCurrencyId}
                    hasStore={hasStore}
                    selectedAddress={selectedBranchAddress}
                    selectedCurrency={selectedCurrency}
                    onCountryChange={setSelectedBranchCountryId}
                    onAddressChange={setSelectedBranchAddressId}
                    onCurrencyChange={setSelectedCurrencyId}
                    onHasStoreChange={setHasStore}
                />
            ) : null}

            {currentStep === 'departments' ? (
                <DepartmentsStep
                    departmentRows={departmentRows}
                    setDepartmentRows={setDepartmentRows}
                />
            ) : null}

            {currentStep === 'staff' ? (
                <StaffStep
                    departments={departments}
                    staffPositions={staffPositions}
                    staffTypes={staffTypes}
                    selectedDepartmentIds={selectedDepartmentIds}
                    selectedStaffPositionId={selectedStaffPositionId}
                    selectedStaffType={selectedStaffType}
                    setSelectedDepartmentIds={setSelectedDepartmentIds}
                    setSelectedStaffPositionId={setSelectedStaffPositionId}
                    setSelectedStaffType={setSelectedStaffType}
                />
            ) : null}
        </OnboardingLayout>
    );
}
