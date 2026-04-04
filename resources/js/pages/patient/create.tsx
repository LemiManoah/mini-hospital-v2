import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PatientCreatePageProps } from '@/types/patient';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, UserRoundPlus } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: 'Register Patient', href: '/patients/create' },
];

const ageInputModeOptions = [
    { value: 'dob', label: 'Date of Birth' },
    { value: 'age', label: 'Current Age' },
];

const ageUnitOptions = [
    { value: 'year', label: 'Years' },
    { value: 'month', label: 'Months' },
    { value: 'day', label: 'Days' },
];

const billingTypeOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'insurance', label: 'Insurance' },
];

const formatAddressLabel = (address: { city: string; district: string | null }) =>
    `${address.city}${address.district ? `, ${address.district}` : ''}`;

const formatDoctorLabel = (doctor: {
    first_name: string;
    last_name: string;
}) => `${doctor.first_name} ${doctor.last_name}`;

export default function PatientCreate({
    countries,
    addresses,
    companies,
    packages,
    clinics,
    doctors,
    visitTypes,
    genderOptions,
    maritalStatusOptions,
    bloodGroupOptions,
    religionOptions,
    kinRelationshipOptions,
}: PatientCreatePageProps) {
    const [ageInputMode, setAgeInputMode] = useState<'dob' | 'age'>('dob');
    const [gender, setGender] = useState(genderOptions[0]?.value ?? '');
    const [ageUnits, setAgeUnits] = useState<'year' | 'month' | 'day'>('year');
    const [maritalStatus, setMaritalStatus] = useState('');
    const [bloodGroup, setBloodGroup] = useState('');
    const [religion, setReligion] = useState('');
    const [kinRelationship, setKinRelationship] = useState('');
    const [countryId, setCountryId] = useState('');
    const [addressId, setAddressId] = useState('');
    const [visitType, setVisitType] = useState(visitTypes[0]?.value ?? '');
    const [clinicId, setClinicId] = useState('');
    const [doctorId, setDoctorId] = useState('');
    const [billingType, setBillingType] = useState<'cash' | 'insurance'>(
        'cash',
    );
    const [companyId, setCompanyId] = useState('');
    const [packageId, setPackageId] = useState('');

    const filteredPackages = useMemo(
        () => packages.filter((pkg) => pkg.insurance_company_id === companyId),
        [packages, companyId],
    );
    const countryOptions = useMemo(
        () =>
            countries.map((country) => ({
                value: country.id,
                label: country.country_name,
            })),
        [countries],
    );
    const addressOptions = useMemo(
        () =>
            addresses.map((address) => ({
                value: address.id,
                label: formatAddressLabel(address),
            })),
        [addresses],
    );
    const clinicOptions = useMemo(
        () =>
            clinics.map((clinic) => ({
                value: clinic.id,
                label: clinic.clinic_name,
            })),
        [clinics],
    );
    const doctorOptions = useMemo(
        () =>
            doctors.map((doctor) => ({
                value: doctor.id,
                label: formatDoctorLabel(doctor),
            })),
        [doctors],
    );
    const companyOptions = useMemo(
        () =>
            companies.map((company) => ({
                value: company.id,
                label: company.name,
            })),
        [companies],
    );
    const packageOptions = useMemo(
        () =>
            filteredPackages.map((pkg) => ({
                value: pkg.id,
                label: pkg.name,
            })),
        [filteredPackages],
    );

    useEffect(() => {
        if (billingType === 'cash') {
            setCompanyId('');
            setPackageId('');
        }
    }, [billingType]);

    useEffect(() => {
        if (!filteredPackages.some((pkg) => pkg.id === packageId)) {
            setPackageId('');
        }
    }, [filteredPackages, packageId]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Register Patient & Start Visit" />

            <div className="m-4 max-w-5xl space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Register Patient & Start Visit
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            One transaction creates the patient, visit, and
                            payer snapshot.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/patients">Back</Link>
                    </Button>
                </div>

                <Form
                    action="/patients"
                    method="post"
                    onSuccess={() =>
                        toast.success(
                            'Patient registered and visit started successfully.',
                        )
                    }
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="age_input_mode"
                                value={ageInputMode}
                            />
                            <input type="hidden" name="gender" value={gender} />
                            <input
                                type="hidden"
                                name="age_units"
                                value={ageUnits}
                            />
                            <input
                                type="hidden"
                                name="marital_status"
                                value={maritalStatus}
                            />
                            <input
                                type="hidden"
                                name="blood_group"
                                value={bloodGroup}
                            />
                            <input
                                type="hidden"
                                name="religion"
                                value={religion}
                            />
                            <input
                                type="hidden"
                                name="next_of_kin_relationship"
                                value={kinRelationship}
                            />
                            <input
                                type="hidden"
                                name="country_id"
                                value={countryId}
                            />
                            <input
                                type="hidden"
                                name="address_id"
                                value={addressId}
                            />
                            <input
                                type="hidden"
                                name="visit_type"
                                value={visitType}
                            />
                            <input
                                type="hidden"
                                name="clinic_id"
                                value={clinicId}
                            />
                            <input
                                type="hidden"
                                name="doctor_id"
                                value={doctorId}
                            />
                            <input
                                type="hidden"
                                name="billing_type"
                                value={billingType}
                            />
                            <input
                                type="hidden"
                                name="insurance_company_id"
                                value={companyId}
                            />
                            <input
                                type="hidden"
                                name="insurance_package_id"
                                value={packageId}
                            />
                            <input
                                type="hidden"
                                name="redirect_to"
                                id="redirect_to"
                                value="show"
                            />

                            <Card>
                                <CardHeader>
                                    <CardTitle>Patient Details</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="first_name">
                                            First Name
                                        </Label>
                                        <Input
                                            id="first_name"
                                            name="first_name"
                                            required
                                            autoFocus
                                        />
                                        <InputError
                                            message={errors.first_name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="last_name">
                                            Last Name
                                        </Label>
                                        <Input
                                            id="last_name"
                                            name="last_name"
                                            required
                                        />
                                        <InputError
                                            message={errors.last_name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="middle_name">
                                            Middle Name
                                        </Label>
                                        <Input
                                            id="middle_name"
                                            name="middle_name"
                                        />
                                        <InputError
                                            message={errors.middle_name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="gender">Gender</Label>
                                        <SearchableSelect
                                            inputId="gender"
                                            options={genderOptions}
                                            value={gender}
                                            onValueChange={setGender}
                                            placeholder="Select gender"
                                            emptyMessage="No gender options available."
                                            invalid={Boolean(errors.gender)}
                                        />
                                        <InputError message={errors.gender} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="age_input_mode">
                                            Birth Date Type
                                        </Label>
                                        <SearchableSelect
                                            inputId="age_input_mode"
                                            options={ageInputModeOptions}
                                            value={ageInputMode}
                                            onValueChange={(value) =>
                                                setAgeInputMode(
                                                    value as 'dob' | 'age',
                                                )
                                            }
                                            placeholder="Select mode"
                                        />
                                    </div>
                                    {ageInputMode === 'dob' ? (
                                        <div className="grid gap-2">
                                            <Label htmlFor="date_of_birth">
                                                Date of Birth
                                            </Label>
                                            <Input
                                                id="date_of_birth"
                                                name="date_of_birth"
                                                type="date"
                                            />
                                            <InputError
                                                message={errors.date_of_birth}
                                            />
                                        </div>
                                    ) : (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="age">Age</Label>
                                                <Input
                                                    id="age"
                                                    name="age"
                                                    type="number"
                                                    min="0"
                                                />
                                                <InputError
                                                    message={errors.age}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="age_units">
                                                    Units
                                                </Label>
                                                <SearchableSelect
                                                    inputId="age_units"
                                                    options={ageUnitOptions}
                                                    value={ageUnits}
                                                    onValueChange={(value) =>
                                                        setAgeUnits(
                                                            value as
                                                                | 'year'
                                                                | 'month'
                                                                | 'day',
                                                        )
                                                    }
                                                    placeholder="Select units"
                                                />
                                            </div>
                                        </>
                                    )}
                                    <div className="grid gap-2">
                                        <Label htmlFor="phone_number">
                                            Phone Number
                                        </Label>
                                        <Input
                                            id="phone_number"
                                            name="phone_number"
                                            required
                                        />
                                        <InputError
                                            message={errors.phone_number}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="alternative_phone">
                                            Alternative Phone
                                        </Label>
                                        <Input
                                            id="alternative_phone"
                                            name="alternative_phone"
                                        />
                                        <InputError
                                            message={errors.alternative_phone}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="country_id">
                                            Country
                                        </Label>
                                        <SearchableSelect
                                            inputId="country_id"
                                            options={countryOptions}
                                            value={countryId}
                                            onValueChange={setCountryId}
                                            placeholder="Select country"
                                            emptyMessage="No countries available."
                                            allowClear
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="address_id">
                                            City / Address
                                        </Label>
                                        <SearchableSelect
                                            inputId="address_id"
                                            options={addressOptions}
                                            value={addressId}
                                            onValueChange={setAddressId}
                                            placeholder="Select address"
                                            emptyMessage="No addresses available."
                                            allowClear
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="marital_status">
                                            Marital Status
                                        </Label>
                                        <SearchableSelect
                                            inputId="marital_status"
                                            options={maritalStatusOptions}
                                            value={maritalStatus}
                                            onValueChange={setMaritalStatus}
                                            placeholder="Select marital status"
                                            emptyMessage="No marital status options available."
                                            allowClear
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="occupation">
                                            Occupation
                                        </Label>
                                        <Input
                                            id="occupation"
                                            name="occupation"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="religion">
                                            Religion
                                        </Label>
                                        <SearchableSelect
                                            inputId="religion"
                                            options={religionOptions}
                                            value={religion}
                                            onValueChange={setReligion}
                                            placeholder="Select religion"
                                            emptyMessage="No religion options available."
                                            allowClear
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="blood_group">
                                            Blood Group
                                        </Label>
                                        <SearchableSelect
                                            inputId="blood_group"
                                            options={bloodGroupOptions}
                                            value={bloodGroup}
                                            onValueChange={setBloodGroup}
                                            placeholder="Select blood group"
                                            emptyMessage="No blood group options available."
                                            allowClear
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Next of Kin</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="next_of_kin_name">
                                            Name
                                        </Label>
                                        <Input
                                            id="next_of_kin_name"
                                            name="next_of_kin_name"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="next_of_kin_phone">
                                            Phone
                                        </Label>
                                        <Input
                                            id="next_of_kin_phone"
                                            name="next_of_kin_phone"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="next_of_kin_relationship">
                                            Relationship
                                        </Label>
                                        <SearchableSelect
                                            inputId="next_of_kin_relationship"
                                            options={kinRelationshipOptions}
                                            value={kinRelationship}
                                            onValueChange={setKinRelationship}
                                            placeholder="Select relationship"
                                            emptyMessage="No relationship options available."
                                            allowClear
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Visit & Billing</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="visit_type">
                                            Visit Type
                                        </Label>
                                        <SearchableSelect
                                            inputId="visit_type"
                                            options={visitTypes}
                                            value={visitType}
                                            onValueChange={setVisitType}
                                            placeholder="Select visit type"
                                            emptyMessage="No visit types available."
                                            invalid={Boolean(errors.visit_type)}
                                        />
                                        <InputError
                                            message={errors.visit_type}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="clinic_id">
                                            Clinic
                                        </Label>
                                        <SearchableSelect
                                            inputId="clinic_id"
                                            options={clinicOptions}
                                            value={clinicId}
                                            onValueChange={setClinicId}
                                            placeholder="Select clinic"
                                            emptyMessage="No clinics available."
                                            allowClear
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="doctor_id">
                                            Doctor
                                        </Label>
                                        <SearchableSelect
                                            inputId="doctor_id"
                                            options={doctorOptions}
                                            value={doctorId}
                                            onValueChange={setDoctorId}
                                            placeholder="Select doctor"
                                            emptyMessage="No doctors available."
                                            allowClear
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="billing_type">
                                            Billing Type
                                        </Label>
                                        <SearchableSelect
                                            inputId="billing_type"
                                            options={billingTypeOptions}
                                            value={billingType}
                                            onValueChange={(value) =>
                                                setBillingType(
                                                    value as
                                                        | 'cash'
                                                        | 'insurance',
                                                )
                                            }
                                            placeholder="Select billing type"
                                            invalid={Boolean(errors.billing_type)}
                                        />
                                        <InputError
                                            message={errors.billing_type}
                                        />
                                    </div>
                                    {billingType === 'insurance' && (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="insurance_company_id">
                                                    Insurer
                                                </Label>
                                                <SearchableSelect
                                                    inputId="insurance_company_id"
                                                    options={companyOptions}
                                                    value={companyId}
                                                    onValueChange={setCompanyId}
                                                    placeholder="Select insurer"
                                                    emptyMessage="No insurers available."
                                                    allowClear
                                                    invalid={Boolean(
                                                        errors.insurance_company_id,
                                                    )}
                                                />
                                                <InputError
                                                    message={
                                                        errors.insurance_company_id
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="insurance_package_id">
                                                    Package
                                                </Label>
                                                <SearchableSelect
                                                    inputId="insurance_package_id"
                                                    options={packageOptions}
                                                    value={packageId}
                                                    onValueChange={setPackageId}
                                                    placeholder="Select package"
                                                    emptyMessage="No packages available."
                                                    allowClear
                                                    invalid={Boolean(
                                                        errors.insurance_package_id,
                                                    )}
                                                />
                                                <InputError
                                                    message={
                                                        errors.insurance_package_id
                                                    }
                                                />
                                            </div>
                                        </>
                                    )}
                                    <div className="flex items-center gap-2 pt-8">
                                        <input
                                            type="checkbox"
                                            id="is_emergency"
                                            name="is_emergency"
                                            value="1"
                                            className="h-4 w-4 rounded border-gray-300"
                                        />
                                        <Label
                                            htmlFor="is_emergency"
                                            className="font-normal"
                                        >
                                            Emergency Visit
                                        </Label>
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    onClick={() => {
                                        const input = document.getElementById(
                                            'redirect_to',
                                        ) as HTMLInputElement | null;
                                        if (input) input.value = 'show';
                                    }}
                                >
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <UserRoundPlus className="mr-2 h-4 w-4" />
                                    )}
                                    Register & Open Profile
                                </Button>
                                <Button
                                    type="submit"
                                    variant="secondary"
                                    disabled={processing}
                                    onClick={() => {
                                        const input = document.getElementById(
                                            'redirect_to',
                                        ) as HTMLInputElement | null;
                                        if (input) input.value = 'list';
                                    }}
                                >
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <CheckCircle2 className="mr-2 h-4 w-4" />
                                    )}
                                    Register & Return List
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
