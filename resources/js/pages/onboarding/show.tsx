import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AuthLayout from '@/layouts/auth-layout';
import { cn } from '@/lib/utils';
import { Form, Head } from '@inertiajs/react';
import {
    Building2,
    CheckCircle2,
    LoaderCircle,
    MapPinned,
    Plus,
    Sparkles,
    Stethoscope,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';

type Step = {
    key: 'profile' | 'branch' | 'departments';
    title: string;
    description: string;
    status: 'complete' | 'current' | 'upcoming';
};

type Option = {
    id: string;
    name: string;
};

type SelectOption = {
    value: string;
    label: string;
};

type CurrencyOption = {
    id: string;
    name: string;
    code: string;
    symbol: string | null;
};

type DepartmentDraft = {
    name: string;
    location: string;
    is_clinical: boolean;
};

type OnboardingPageProps = {
    tenant: {
        id: string;
        name: string;
        domain: string | null;
        facility_level: string;
        facility_level_label: string;
        country_id: string | null;
        address: {
            city: string | null;
            district: string | null;
            state: string | null;
        } | null;
    };
    currentStep: Step['key'];
    steps: Step[];
    facilityLevels: SelectOption[];
    countries: Option[];
    currencies: CurrencyOption[];
    branch: {
        name: string;
        branch_code: string;
        email: string | null;
        main_contact: string | null;
        other_contact: string | null;
        currency_id: string | null;
        has_store: boolean;
        address: {
            city: string | null;
            district: string | null;
            state: string | null;
            country_id: string | null;
        } | null;
    } | null;
    departments: {
        id: string;
        name: string;
        location: string | null;
        is_clinical: boolean;
    }[];
};

const defaultDepartments = (): DepartmentDraft[] => [
    {
        name: 'Outpatient',
        location: 'Ground floor',
        is_clinical: true,
    },
    {
        name: 'Emergency',
        location: 'Front wing',
        is_clinical: true,
    },
    {
        name: 'Laboratory',
        location: 'Diagnostics block',
        is_clinical: true,
    },
];

const emptyDepartment = (): DepartmentDraft => ({
    name: '',
    location: '',
    is_clinical: true,
});

function stepClasses(status: Step['status']): string {
    return (
        {
            complete: 'border-emerald-200 bg-emerald-50 text-emerald-900',
            current: 'border-zinc-900 bg-zinc-900 text-zinc-50',
            upcoming: 'border-zinc-200 bg-white text-zinc-600',
        }[status] ?? 'border-zinc-200 bg-white text-zinc-600'
    );
}

function stepBadgeLabel(status: Step['status']): string {
    return (
        {
            complete: 'Done',
            current: 'Active',
            upcoming: 'Locked',
        }[status] ?? 'Locked'
    );
}

export default function OnboardingShow({
    tenant,
    currentStep,
    steps,
    facilityLevels,
    countries,
    currencies,
    branch,
    departments,
}: OnboardingPageProps) {
    const [selectedFacilityLevel, setSelectedFacilityLevel] = useState(
        tenant.facility_level,
    );
    const [selectedCountryId, setSelectedCountryId] = useState(
        tenant.country_id ?? '',
    );
    const [selectedBranchCountryId, setSelectedBranchCountryId] = useState(
        branch?.address?.country_id ?? tenant.country_id ?? '',
    );
    const [selectedCurrencyId, setSelectedCurrencyId] = useState(
        branch?.currency_id ?? currencies[0]?.id ?? '',
    );
    const [hasStore, setHasStore] = useState(branch?.has_store ?? false);
    const [departmentRows, setDepartmentRows] = useState<DepartmentDraft[]>(
        departments.length > 0
            ? departments.map((department) => ({
                  name: department.name,
                  location: department.location ?? '',
                  is_clinical: department.is_clinical,
              }))
            : defaultDepartments(),
    );

    const selectedLevelLabel = useMemo(
        () =>
            facilityLevels.find(
                (option) => option.value === selectedFacilityLevel,
            )?.label ?? tenant.facility_level_label,
        [facilityLevels, selectedFacilityLevel, tenant.facility_level_label],
    );
    const selectedCurrency = useMemo(
        () => currencies.find((currency) => currency.id === selectedCurrencyId),
        [currencies, selectedCurrencyId],
    );

    return (
        <AuthLayout
            title="Set up your hospital workspace"
            description="We will finish the core workspace basics in three short steps so the team can start using the system immediately."
        >
            <Head title="Onboarding" />
            <div className="space-y-6">
                <div className="rounded-3xl border border-zinc-200 bg-[radial-gradient(circle_at_top_left,_rgba(22,163,74,0.16),_transparent_35%),linear-gradient(135deg,_rgba(255,255,255,0.96),_rgba(244,244,245,0.94))] p-6">
                    <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div className="space-y-3">
                            <Badge
                                variant="outline"
                                className="border-emerald-200 bg-emerald-50 text-emerald-800"
                            >
                                Phase 0 · Milestone 2
                            </Badge>
                            <div className="space-y-2">
                                <h1 className="text-3xl font-semibold tracking-tight text-zinc-950">
                                    {tenant.name}
                                </h1>
                                <p className="max-w-2xl text-sm leading-6 text-zinc-600">
                                    Confirm the hospital identity, create the
                                    primary branch, and bootstrap departments.
                                    After that, the workspace opens fully for
                                    daily operations.
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                                <p className="text-xs tracking-[0.18em] text-zinc-500 uppercase">
                                    Current stage
                                </p>
                                <p className="mt-1 font-medium text-zinc-950">
                                    {steps.find(
                                        (step) => step.key === currentStep,
                                    )?.title ?? 'Onboarding'}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                                <p className="text-xs tracking-[0.18em] text-zinc-500 uppercase">
                                    Facility level
                                </p>
                                <p className="mt-1 font-medium text-zinc-950">
                                    {selectedLevelLabel}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                                <p className="text-xs tracking-[0.18em] text-zinc-500 uppercase">
                                    Departments ready
                                </p>
                                <p className="mt-1 font-medium text-zinc-950">
                                    {departmentRows.length}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
                    <div className="space-y-4">
                        {steps.map((step, index) => (
                            <div
                                key={step.key}
                                className={cn(
                                    'rounded-2xl border p-4 transition-colors',
                                    stepClasses(step.status),
                                )}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-xs tracking-[0.2em] uppercase opacity-70">
                                            Step {index + 1}
                                        </p>
                                        <p className="mt-1 font-semibold">
                                            {step.title}
                                        </p>
                                    </div>
                                    <Badge
                                        variant="secondary"
                                        className={cn(
                                            'border-0',
                                            step.status === 'current'
                                                ? 'bg-white/15 text-white'
                                                : '',
                                        )}
                                    >
                                        {stepBadgeLabel(step.status)}
                                    </Badge>
                                </div>
                                <p className="mt-3 text-sm leading-6 opacity-80">
                                    {step.description}
                                </p>
                            </div>
                        ))}

                        <Card className="border-dashed">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Sparkles className="h-4 w-4" />
                                    What this unlocks
                                </CardTitle>
                                <CardDescription>
                                    Completing these steps leaves the workspace
                                    ready for staff and clinical modules.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm text-muted-foreground">
                                <p>
                                    The owner account will be attached to the
                                    primary branch automatically.
                                </p>
                                <p>
                                    Department bootstrap gives triage,
                                    consultation, and queue workflows an
                                    operational structure to hang on.
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card
                            className={cn(
                                currentStep !== 'profile' &&
                                    'border-emerald-200',
                            )}
                        >
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Building2 className="h-5 w-5" />
                                    Hospital profile
                                </CardTitle>
                                <CardDescription>
                                    Name the workspace, confirm the facility
                                    level, and pin the hospital to a location.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    method="patch"
                                    action="/onboarding/profile"
                                    className="space-y-6"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <input
                                                type="hidden"
                                                name="facility_level"
                                                value={selectedFacilityLevel}
                                            />
                                            <input
                                                type="hidden"
                                                name="country_id"
                                                value={selectedCountryId}
                                            />

                                            <div className="grid gap-5 md:grid-cols-2">
                                                <div className="grid gap-2 md:col-span-2">
                                                    <Label htmlFor="tenant_name">
                                                        Hospital or workspace
                                                        name
                                                    </Label>
                                                    <Input
                                                        id="tenant_name"
                                                        name="name"
                                                        defaultValue={
                                                            tenant.name
                                                        }
                                                        placeholder="Mini Hospital Kampala"
                                                        disabled={
                                                            currentStep !==
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.name}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="domain">
                                                        Workspace domain slug
                                                    </Label>
                                                    <Input
                                                        id="domain"
                                                        name="domain"
                                                        defaultValue={
                                                            tenant.domain ?? ''
                                                        }
                                                        placeholder="mini-hospital-kla"
                                                        disabled={
                                                            currentStep !==
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.domain}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label>
                                                        Facility level
                                                    </Label>
                                                    <Select
                                                        value={
                                                            selectedFacilityLevel
                                                        }
                                                        onValueChange={
                                                            setSelectedFacilityLevel
                                                        }
                                                        disabled={
                                                            currentStep !==
                                                            'profile'
                                                        }
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select facility level" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {facilityLevels.map(
                                                                (option) => (
                                                                    <SelectItem
                                                                        key={
                                                                            option.value
                                                                        }
                                                                        value={
                                                                            option.value
                                                                        }
                                                                    >
                                                                        {
                                                                            option.label
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError
                                                        message={
                                                            errors.facility_level
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label>Country</Label>
                                                    <Select
                                                        value={
                                                            selectedCountryId ||
                                                            'none'
                                                        }
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            setSelectedCountryId(
                                                                value === 'none'
                                                                    ? ''
                                                                    : value,
                                                            )
                                                        }
                                                        disabled={
                                                            currentStep !==
                                                            'profile'
                                                        }
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select country" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="none">
                                                                Select later
                                                            </SelectItem>
                                                            {countries.map(
                                                                (country) => (
                                                                    <SelectItem
                                                                        key={
                                                                            country.id
                                                                        }
                                                                        value={
                                                                            country.id
                                                                        }
                                                                    >
                                                                        {
                                                                            country.name
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError
                                                        message={
                                                            errors.country_id
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="city">
                                                        City
                                                    </Label>
                                                    <Input
                                                        id="city"
                                                        name="city"
                                                        defaultValue={
                                                            tenant.address
                                                                ?.city ?? ''
                                                        }
                                                        placeholder="Kampala"
                                                        disabled={
                                                            currentStep !==
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.city}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="district">
                                                        District
                                                    </Label>
                                                    <Input
                                                        id="district"
                                                        name="district"
                                                        defaultValue={
                                                            tenant.address
                                                                ?.district ?? ''
                                                        }
                                                        placeholder="Central"
                                                        disabled={
                                                            currentStep !==
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.district
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2 md:col-span-2">
                                                    <Label htmlFor="state">
                                                        State or region
                                                    </Label>
                                                    <Input
                                                        id="state"
                                                        name="state"
                                                        defaultValue={
                                                            tenant.address
                                                                ?.state ?? ''
                                                        }
                                                        placeholder="Central Region"
                                                        disabled={
                                                            currentStep !==
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.state}
                                                    />
                                                </div>
                                            </div>

                                            <div className="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4 text-sm text-zinc-600 sm:flex-row sm:items-center sm:justify-between">
                                                <p>
                                                    Save the hospital profile to
                                                    unlock branch setup.
                                                </p>
                                                <Button
                                                    type="submit"
                                                    disabled={
                                                        processing ||
                                                        currentStep !==
                                                            'profile'
                                                    }
                                                >
                                                    {processing ? (
                                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                                    ) : null}
                                                    Save and continue
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>

                        <Card
                            className={cn(
                                currentStep === 'profile' &&
                                    'opacity-70 grayscale-[0.1]',
                                currentStep === 'departments' &&
                                    'border-emerald-200',
                            )}
                        >
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <MapPinned className="h-5 w-5" />
                                    Primary branch
                                </CardTitle>
                                <CardDescription>
                                    This branch becomes the operational home for
                                    the owner account and the first workflows.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    method="post"
                                    action="/onboarding/branch"
                                    className="space-y-6"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <input
                                                type="hidden"
                                                name="currency_id"
                                                value={selectedCurrencyId}
                                            />
                                            <input
                                                type="hidden"
                                                name="country_id"
                                                value={selectedBranchCountryId}
                                            />
                                            <input
                                                type="hidden"
                                                name="has_store"
                                                value={hasStore ? '1' : '0'}
                                            />

                                            <div className="grid gap-5 md:grid-cols-2">
                                                <div className="grid gap-2 md:col-span-2">
                                                    <Label htmlFor="branch_name">
                                                        Branch name
                                                    </Label>
                                                    <Input
                                                        id="branch_name"
                                                        name="name"
                                                        defaultValue={
                                                            branch?.name ??
                                                            `${tenant.name} Main Branch`
                                                        }
                                                        placeholder="Mini Hospital Main Branch"
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.name}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="branch_code">
                                                        Branch code
                                                    </Label>
                                                    <Input
                                                        id="branch_code"
                                                        name="branch_code"
                                                        defaultValue={
                                                            branch?.branch_code ??
                                                            'MAIN'
                                                        }
                                                        placeholder="MAIN"
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.branch_code
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label>
                                                        Billing currency
                                                    </Label>
                                                    <Select
                                                        value={
                                                            selectedCurrencyId
                                                        }
                                                        onValueChange={
                                                            setSelectedCurrencyId
                                                        }
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select currency" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {currencies.map(
                                                                (currency) => (
                                                                    <SelectItem
                                                                        key={
                                                                            currency.id
                                                                        }
                                                                        value={
                                                                            currency.id
                                                                        }
                                                                    >
                                                                        {
                                                                            currency.name
                                                                        }{' '}
                                                                        (
                                                                        {
                                                                            currency.code
                                                                        }
                                                                        )
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError
                                                        message={
                                                            errors.currency_id
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="branch_email">
                                                        Branch email
                                                    </Label>
                                                    <Input
                                                        id="branch_email"
                                                        name="email"
                                                        type="email"
                                                        defaultValue={
                                                            branch?.email ?? ''
                                                        }
                                                        placeholder="branch@hospital.com"
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.email}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="main_contact">
                                                        Primary phone
                                                    </Label>
                                                    <Input
                                                        id="main_contact"
                                                        name="main_contact"
                                                        defaultValue={
                                                            branch?.main_contact ??
                                                            ''
                                                        }
                                                        placeholder="+256700000000"
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.main_contact
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="other_contact">
                                                        Alternate phone
                                                    </Label>
                                                    <Input
                                                        id="other_contact"
                                                        name="other_contact"
                                                        defaultValue={
                                                            branch?.other_contact ??
                                                            ''
                                                        }
                                                        placeholder="+256701111111"
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.other_contact
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="branch_city">
                                                        City
                                                    </Label>
                                                    <Input
                                                        id="branch_city"
                                                        name="city"
                                                        defaultValue={
                                                            branch?.address
                                                                ?.city ??
                                                            tenant.address
                                                                ?.city ??
                                                            ''
                                                        }
                                                        placeholder="Kampala"
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.city}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="branch_district">
                                                        District
                                                    </Label>
                                                    <Input
                                                        id="branch_district"
                                                        name="district"
                                                        defaultValue={
                                                            branch?.address
                                                                ?.district ??
                                                            tenant.address
                                                                ?.district ??
                                                            ''
                                                        }
                                                        placeholder="Central"
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.district
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="branch_state">
                                                        State or region
                                                    </Label>
                                                    <Input
                                                        id="branch_state"
                                                        name="state"
                                                        defaultValue={
                                                            branch?.address
                                                                ?.state ??
                                                            tenant.address
                                                                ?.state ??
                                                            ''
                                                        }
                                                        placeholder="Central Region"
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.state}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label>
                                                        Branch country
                                                    </Label>
                                                    <Select
                                                        value={
                                                            selectedBranchCountryId ||
                                                            'none'
                                                        }
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            setSelectedBranchCountryId(
                                                                value === 'none'
                                                                    ? ''
                                                                    : value,
                                                            )
                                                        }
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select country" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="none">
                                                                Select later
                                                            </SelectItem>
                                                            {countries.map(
                                                                (country) => (
                                                                    <SelectItem
                                                                        key={
                                                                            country.id
                                                                        }
                                                                        value={
                                                                            country.id
                                                                        }
                                                                    >
                                                                        {
                                                                            country.name
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError
                                                        message={
                                                            errors.country_id
                                                        }
                                                    />
                                                </div>
                                            </div>

                                            <div className="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4">
                                                <div className="flex items-start gap-3">
                                                    <Checkbox
                                                        id="has_store"
                                                        checked={hasStore}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            setHasStore(
                                                                checked ===
                                                                    true,
                                                            )
                                                        }
                                                        disabled={
                                                            currentStep ===
                                                            'profile'
                                                        }
                                                    />
                                                    <div className="space-y-1">
                                                        <Label htmlFor="has_store">
                                                            This branch has a
                                                            store or pharmacy
                                                            location
                                                        </Label>
                                                        <p className="text-sm text-muted-foreground">
                                                            Useful now for
                                                            branch readiness and
                                                            later for inventory
                                                            rollout.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            {selectedCurrency ? (
                                                <div className="rounded-2xl border border-sky-200 bg-sky-50/70 p-4 text-sm text-sky-900">
                                                    Branch transactions will use{' '}
                                                    <span className="font-medium">
                                                        {selectedCurrency.name}{' '}
                                                        ({selectedCurrency.code}
                                                        )
                                                    </span>
                                                    {selectedCurrency.symbol
                                                        ? ` with symbol ${selectedCurrency.symbol}.`
                                                        : '.'}
                                                </div>
                                            ) : null}

                                            <div className="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4 text-sm text-zinc-600 sm:flex-row sm:items-center sm:justify-between">
                                                <p>
                                                    Save the main branch to
                                                    unlock department bootstrap.
                                                </p>
                                                <Button
                                                    type="submit"
                                                    disabled={
                                                        processing ||
                                                        currentStep ===
                                                            'profile'
                                                    }
                                                >
                                                    {processing ? (
                                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                                    ) : null}
                                                    Save branch
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>

                        <Card
                            className={cn(
                                currentStep !== 'departments' && 'opacity-70',
                            )}
                        >
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Stethoscope className="h-5 w-5" />
                                    Department bootstrap
                                </CardTitle>
                                <CardDescription>
                                    Start with the core operational units the
                                    team needs on day one. You can refine these
                                    later without losing the initial structure.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    method="post"
                                    action="/onboarding/departments"
                                    className="space-y-6"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="space-y-4">
                                                {departmentRows.map(
                                                    (department, index) => (
                                                        <div
                                                            key={`${index}-${department.name}`}
                                                            className="rounded-2xl border p-4"
                                                        >
                                                            <div className="grid gap-4 md:grid-cols-2">
                                                                <input
                                                                    type="hidden"
                                                                    name={`departments[${index}][is_clinical]`}
                                                                    value={
                                                                        department.is_clinical
                                                                            ? '1'
                                                                            : '0'
                                                                    }
                                                                />

                                                                <div className="grid gap-2">
                                                                    <Label>
                                                                        Department
                                                                        name
                                                                    </Label>
                                                                    <Input
                                                                        value={
                                                                            department.name
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            setDepartmentRows(
                                                                                departmentRows.map(
                                                                                    (
                                                                                        row,
                                                                                        rowIndex,
                                                                                    ) =>
                                                                                        rowIndex ===
                                                                                        index
                                                                                            ? {
                                                                                                  ...row,
                                                                                                  name: event
                                                                                                      .target
                                                                                                      .value,
                                                                                              }
                                                                                            : row,
                                                                                ),
                                                                            )
                                                                        }
                                                                        placeholder="Outpatient"
                                                                        disabled={
                                                                            currentStep !==
                                                                            'departments'
                                                                        }
                                                                        name={`departments[${index}][name]`}
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors[
                                                                                `departments.${index}.name`
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>

                                                                <div className="grid gap-2">
                                                                    <Label>
                                                                        Location
                                                                    </Label>
                                                                    <Input
                                                                        value={
                                                                            department.location
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            setDepartmentRows(
                                                                                departmentRows.map(
                                                                                    (
                                                                                        row,
                                                                                        rowIndex,
                                                                                    ) =>
                                                                                        rowIndex ===
                                                                                        index
                                                                                            ? {
                                                                                                  ...row,
                                                                                                  location:
                                                                                                      event
                                                                                                          .target
                                                                                                          .value,
                                                                                              }
                                                                                            : row,
                                                                                ),
                                                                            )
                                                                        }
                                                                        placeholder="Ground floor"
                                                                        disabled={
                                                                            currentStep !==
                                                                            'departments'
                                                                        }
                                                                        name={`departments[${index}][location]`}
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors[
                                                                                `departments.${index}.location`
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                            </div>

                                                            <div className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                                                <div className="flex items-start gap-3">
                                                                    <Checkbox
                                                                        checked={
                                                                            department.is_clinical
                                                                        }
                                                                        onCheckedChange={(
                                                                            checked,
                                                                        ) =>
                                                                            setDepartmentRows(
                                                                                departmentRows.map(
                                                                                    (
                                                                                        row,
                                                                                        rowIndex,
                                                                                    ) =>
                                                                                        rowIndex ===
                                                                                        index
                                                                                            ? {
                                                                                                  ...row,
                                                                                                  is_clinical:
                                                                                                      checked ===
                                                                                                      true,
                                                                                              }
                                                                                            : row,
                                                                                ),
                                                                            )
                                                                        }
                                                                        disabled={
                                                                            currentStep !==
                                                                            'departments'
                                                                        }
                                                                    />
                                                                    <div className="space-y-1">
                                                                        <p className="text-sm font-medium">
                                                                            Clinical
                                                                            department
                                                                        </p>
                                                                        <p className="text-sm text-muted-foreground">
                                                                            Keep
                                                                            this
                                                                            on
                                                                            for
                                                                            units
                                                                            like
                                                                            OPD,
                                                                            emergency,
                                                                            wards,
                                                                            or
                                                                            lab.
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    onClick={() =>
                                                                        setDepartmentRows(
                                                                            departmentRows.length >
                                                                                1
                                                                                ? departmentRows.filter(
                                                                                      (
                                                                                          _row,
                                                                                          rowIndex,
                                                                                      ) =>
                                                                                          rowIndex !==
                                                                                          index,
                                                                                  )
                                                                                : departmentRows,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        currentStep !==
                                                                            'departments' ||
                                                                        departmentRows.length <=
                                                                            1
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                    Remove
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    ),
                                                )}
                                            </div>

                                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() =>
                                                        setDepartmentRows([
                                                            ...departmentRows,
                                                            emptyDepartment(),
                                                        ])
                                                    }
                                                    disabled={
                                                        currentStep !==
                                                        'departments'
                                                    }
                                                >
                                                    <Plus className="h-4 w-4" />
                                                    Add department
                                                </Button>

                                                <div className="text-sm text-muted-foreground">
                                                    Start with the essentials.
                                                    More departments can be
                                                    added after onboarding.
                                                </div>
                                            </div>

                                            <InputError
                                                message={
                                                    errors.departments as
                                                        | string
                                                        | undefined
                                                }
                                            />

                                            <div className="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4">
                                                <div className="flex items-start gap-3">
                                                    <CheckCircle2 className="mt-0.5 h-5 w-5 text-emerald-700" />
                                                    <div className="space-y-2">
                                                        <p className="font-medium text-emerald-950">
                                                            Final step
                                                        </p>
                                                        <p className="text-sm leading-6 text-emerald-900/80">
                                                            Submitting this step
                                                            marks onboarding
                                                            complete and sends
                                                            you into the main
                                                            workspace.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex justify-end">
                                                <Button
                                                    type="submit"
                                                    disabled={
                                                        processing ||
                                                        currentStep !==
                                                            'departments'
                                                    }
                                                >
                                                    {processing ? (
                                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                                    ) : null}
                                                    Finish onboarding
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>

                        <Card className="border-dashed bg-zinc-50/50">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Coming next in SaaS Phase 0
                                </CardTitle>
                                <CardDescription>
                                    The next slice will extend this onboarding
                                    with staff invitations, package activation,
                                    and deeper tenant administration.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Textarea
                                    value={`Completed foundation: workspace identity, primary branch, and department bootstrap.\nNext milestone: staff bootstrap, invitations, and package-aware admin controls.`}
                                    readOnly
                                    className="min-h-24 resize-none bg-white"
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}
