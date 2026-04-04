import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PatientEditPageProps } from '@/types/patient';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const ageInputModeOptions = [
    { value: 'dob', label: 'Date of Birth' },
    { value: 'age', label: 'Current Age' },
];

const ageUnitOptions = [
    { value: 'year', label: 'Years' },
    { value: 'month', label: 'Months' },
    { value: 'day', label: 'Days' },
];

const formatAddressLabel = (address: { city: string; district: string | null }) =>
    `${address.city}${address.district ? `, ${address.district}` : ''}`;

export default function PatientEdit({
    patient,
    countries,
    addresses,
    genderOptions,
    maritalStatusOptions,
    bloodGroupOptions,
    religionOptions,
    kinRelationshipOptions,
}: PatientEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Patients', href: '/patients' },
        {
            title: `${patient.first_name} ${patient.last_name}`,
            href: `/patients/${patient.id}`,
        },
        { title: 'Edit', href: `/patients/${patient.id}/edit` },
    ];

    const [ageInputMode, setAgeInputMode] = useState<'dob' | 'age'>(
        patient.date_of_birth ? 'dob' : 'age',
    );
    const [gender, setGender] = useState<string>(
        patient.gender || genderOptions[0]?.value || '',
    );
    const [ageUnits, setAgeUnits] = useState<'year' | 'month' | 'day'>(
        patient.age_units || 'year',
    );
    const [maritalStatus, setMaritalStatus] = useState(
        patient.marital_status || '',
    );
    const [bloodGroup, setBloodGroup] = useState(patient.blood_group || '');
    const [religion, setReligion] = useState(patient.religion || '');
    const [kinRelationship, setKinRelationship] = useState(
        patient.next_of_kin_relationship || '',
    );
    const [countryId, setCountryId] = useState(patient.country_id || '');
    const [addressId, setAddressId] = useState(patient.address_id || '');
    const countryOptions = countries.map((country) => ({
        value: country.id,
        label: country.country_name,
    }));
    const addressOptions = addresses.map((address) => ({
        value: address.id,
        label: formatAddressLabel(address),
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Patient - ${patient.first_name}`} />

            <div className="m-4 max-w-5xl space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Edit Patient</h1>
                        <p className="text-sm text-muted-foreground">
                            Billing now lives on each visit, so this page only
                            updates demographics and contacts.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={`/patients/${patient.id}`}>Back</Link>
                    </Button>
                </div>

                <Form
                    action={`/patients/${patient.id}`}
                    method="put"
                    onSuccess={() =>
                        toast.success('Patient updated successfully.')
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
                                            defaultValue={patient.first_name}
                                            required
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
                                            defaultValue={patient.last_name}
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
                                            defaultValue={
                                                patient.middle_name || ''
                                            }
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
                                        />
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
                                                defaultValue={
                                                    patient.date_of_birth
                                                        ? new Date(
                                                              patient.date_of_birth,
                                                          )
                                                              .toISOString()
                                                              .split('T')[0]
                                                        : ''
                                                }
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
                                                    defaultValue={
                                                        patient.age || ''
                                                    }
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
                                            defaultValue={patient.phone_number}
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
                                            defaultValue={
                                                patient.alternative_phone || ''
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={patient.email || ''}
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
                                            defaultValue={
                                                patient.occupation || ''
                                            }
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
                                            defaultValue={
                                                patient.next_of_kin_name || ''
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="next_of_kin_phone">
                                            Phone
                                        </Label>
                                        <Input
                                            id="next_of_kin_phone"
                                            name="next_of_kin_phone"
                                            defaultValue={
                                                patient.next_of_kin_phone || ''
                                            }
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

                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                ) : null}
                                Save Changes
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
