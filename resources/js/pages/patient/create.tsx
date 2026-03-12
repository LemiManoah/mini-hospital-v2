import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PatientCreatePageProps } from '@/types/patient';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: 'Register Patient', href: '/patients/create' },
];

const maritalStatusOptions = [
    { value: 'single', label: 'Single' },
    { value: 'married', label: 'Married' },
    { value: 'divorced', label: 'Divorced' },
    { value: 'widowed', label: 'Widowed' },
    { value: 'separated', label: 'Separated' },
] as const;

const bloodGroupOptions = [
    { value: 'A+', label: 'A+' },
    { value: 'A-', label: 'A-' },
    { value: 'B+', label: 'B+' },
    { value: 'B-', label: 'B-' },
    { value: 'AB+', label: 'AB+' },
    { value: 'AB-', label: 'AB-' },
    { value: 'O+', label: 'O+' },
    { value: 'O-', label: 'O-' },
    { value: 'unknown', label: 'Unknown' },
] as const;

const religionOptions = [
    { value: 'christian', label: 'Christian' },
    { value: 'muslim', label: 'Muslim' },
    { value: 'hindu', label: 'Hindu' },
    { value: 'buddhist', label: 'Buddhist' },
    { value: 'other', label: 'Other' },
    { value: 'unknown', label: 'Unknown' },
] as const;

const kinRelationshipOptions = [
    { value: 'spouse', label: 'Spouse' },
    { value: 'parent', label: 'Parent' },
    { value: 'child', label: 'Child' },
    { value: 'sibling', label: 'Sibling' },
    { value: 'other', label: 'Other' },
    { value: 'unknown', label: 'Unknown' },
] as const;

export default function PatientCreate({
    countries,
    addresses,
    companies,
    packages,
}: PatientCreatePageProps) {
    const [ageInputMode, setAgeInputMode] = useState<'dob' | 'age'>('dob');
    const [payerType, setPayerType] = useState<'cash' | 'insurance'>('cash');
    const [companyId, setCompanyId] = useState('');
    const [packageId, setPackageId] = useState('');
    const [countryId, setCountryId] = useState('');
    const [addressId, setAddressId] = useState('');
    const [gender, setGender] = useState('unknown');
    const [ageUnits, setAgeUnits] = useState<'year' | 'month' | 'day'>('year');
    const [maritalStatus, setMaritalStatus] = useState('');
    const [bloodGroup, setBloodGroup] = useState('');
    const [religion, setReligion] = useState('');
    const [kinRelationship, setKinRelationship] = useState('');

    const filteredPackages = useMemo(
        () => packages.filter((pkg) => pkg.insurance_company_id === companyId),
        [packages, companyId],
    );

    useEffect(() => {
        if (payerType === 'cash') {
            setCompanyId('');
            setPackageId('');
        }
    }, [payerType]);

    useEffect(() => {
        const packageExists = filteredPackages.some((pkg) => pkg.id === packageId);
        if (!packageExists) {
            setPackageId('');
        }
    }, [filteredPackages, packageId]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Register Patient" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Register Patient
                        </h2>
                        <Button variant="outline" size="sm" asChild className="h-8">
                            <Link href="/patients">Back</Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Register patient demographics and choose cash or insured payer profile.
                    </p>
                </div>
            </div>

            <div className="m-4">
                <Form
                    action="/patients"
                    method="post"
                    onSuccess={() => toast.success('Patient registered successfully.')}
                    className="space-y-8"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-5xl space-y-8">
                            <input type="hidden" name="age_input_mode" value={ageInputMode} />
                            <input type="hidden" name="payer_type" value={payerType} />
                            <input type="hidden" name="insurance_company_id" value={companyId} />
                            <input type="hidden" name="insurance_package_id" value={packageId} />
                            <input type="hidden" name="country_id" value={countryId} />
                            <input type="hidden" name="address_id" value={addressId} />
                            <input type="hidden" name="gender" value={gender} />
                            <input type="hidden" name="age_units" value={ageUnits} />
                            <input type="hidden" name="marital_status" value={maritalStatus} />
                            <input type="hidden" name="blood_group" value={bloodGroup} />
                            <input type="hidden" name="religion" value={religion} />
                            <input type="hidden" name="next_of_kin_relationship" value={kinRelationship} />

                            <Card className="shadow-sm">
                                <CardHeader className="border-b py-4">
                                    <CardTitle className="text-lg">Bio & Demographics</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-6 pt-6">
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div className="grid gap-2">
                                            <Label htmlFor="first_name">First Name</Label>
                                            <Input id="first_name" name="first_name" required autoFocus />
                                            <InputError message={errors.first_name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="last_name">Last Name</Label>
                                            <Input id="last_name" name="last_name" required />
                                            <InputError message={errors.last_name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="middle_name">Middle Name</Label>
                                            <Input id="middle_name" name="middle_name" />
                                            <InputError message={errors.middle_name} />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="gender">Gender</Label>
                                            <Select value={gender} onValueChange={(value) => setGender(value as 'male' | 'female' | 'other' | 'unknown')}>
                                                <SelectTrigger id="gender"><SelectValue placeholder="Select gender" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="male">Male</SelectItem>
                                                    <SelectItem value="female">Female</SelectItem>
                                                    <SelectItem value="other">Other</SelectItem>
                                                    <SelectItem value="unknown">Unknown</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.gender} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="age_input_mode">Birth Date Type</Label>
                                            <Select value={ageInputMode} onValueChange={(value) => setAgeInputMode(value as 'dob' | 'age')}>
                                                <SelectTrigger id="age_input_mode"><SelectValue placeholder="Select mode" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="dob">Date of Birth</SelectItem>
                                                    <SelectItem value="age">Current Age</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        {ageInputMode === 'dob' ? (
                                            <div className="grid gap-2 sm:col-span-2">
                                                <Label htmlFor="date_of_birth">Date of Birth</Label>
                                                <Input id="date_of_birth" name="date_of_birth" type="date" />
                                                <InputError message={errors.date_of_birth} />
                                            </div>
                                        ) : (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="age">Age</Label>
                                                    <Input id="age" name="age" type="number" min="0" />
                                                    <InputError message={errors.age} />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="age_units">Units</Label>
                                                    <Select value={ageUnits} onValueChange={(value) => setAgeUnits(value as 'year' | 'month' | 'day')}>
                                                        <SelectTrigger id="age_units"><SelectValue placeholder="Select units" /></SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="year">Years</SelectItem>
                                                            <SelectItem value="month">Months</SelectItem>
                                                            <SelectItem value="day">Days</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div className="grid gap-2">
                                            <Label htmlFor="marital_status">Marital Status</Label>
                                            <Select value={maritalStatus} onValueChange={setMaritalStatus}>
                                                <SelectTrigger id="marital_status"><SelectValue placeholder="Select marital status" /></SelectTrigger>
                                                <SelectContent>
                                                    {maritalStatusOptions.map((option) => (
                                                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.marital_status} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="occupation">Occupation</Label>
                                            <Input id="occupation" name="occupation" />
                                            <InputError message={errors.occupation} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="religion">Religion</Label>
                                            <Select value={religion} onValueChange={setReligion}>
                                                <SelectTrigger id="religion"><SelectValue placeholder="Select religion" /></SelectTrigger>
                                                <SelectContent>
                                                    {religionOptions.map((option) => (
                                                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.religion} />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div className="grid gap-2">
                                            <Label htmlFor="blood_group">Blood Group</Label>
                                            <Select value={bloodGroup} onValueChange={setBloodGroup}>
                                                <SelectTrigger id="blood_group"><SelectValue placeholder="Select blood group" /></SelectTrigger>
                                                <SelectContent>
                                                    {bloodGroupOptions.map((option) => (
                                                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.blood_group} />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="shadow-sm">
                                <CardHeader className="border-b py-4">
                                    <CardTitle className="text-lg">Contact & Location</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-6 pt-6">
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div className="grid gap-2">
                                            <Label htmlFor="phone_number">Phone Number</Label>
                                            <Input id="phone_number" name="phone_number" required />
                                            <InputError message={errors.phone_number} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="alternative_phone">Alternative Phone</Label>
                                            <Input id="alternative_phone" name="alternative_phone" />
                                            <InputError message={errors.alternative_phone} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="email">Email Address</Label>
                                            <Input id="email" name="email" type="email" />
                                            <InputError message={errors.email} />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="country_id">Country</Label>
                                            <Select value={countryId} onValueChange={setCountryId}>
                                                <SelectTrigger id="country_id"><SelectValue placeholder="Select country" /></SelectTrigger>
                                                <SelectContent>
                                                    {countries.map((country) => (
                                                        <SelectItem key={country.id} value={country.id}>{country.country_name}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.country_id} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="address_id">City / Address</Label>
                                            <Select value={addressId} onValueChange={setAddressId}>
                                                <SelectTrigger id="address_id"><SelectValue placeholder="Select address" /></SelectTrigger>
                                                <SelectContent>
                                                    {addresses.map((address) => (
                                                        <SelectItem key={address.id} value={address.id}>
                                                            {address.city}{address.district ? `, ${address.district}` : ''}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.address_id} />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="shadow-sm">
                                <CardHeader className="border-b py-4">
                                    <CardTitle className="text-lg">Next of Kin</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-6 pt-6">
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="next_of_kin_name">Next of Kin Name</Label>
                                            <Input id="next_of_kin_name" name="next_of_kin_name" />
                                            <InputError message={errors.next_of_kin_name} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="next_of_kin_phone">Next of Kin Phone</Label>
                                            <Input id="next_of_kin_phone" name="next_of_kin_phone" />
                                            <InputError message={errors.next_of_kin_phone} />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="next_of_kin_relationship">Relationship</Label>
                                            <Select value={kinRelationship} onValueChange={setKinRelationship}>
                                                <SelectTrigger id="next_of_kin_relationship"><SelectValue placeholder="Select relationship" /></SelectTrigger>
                                                <SelectContent>
                                                    {kinRelationshipOptions.map((option) => (
                                                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.next_of_kin_relationship} />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="shadow-sm">
                                <CardHeader className="border-b py-4">
                                    <CardTitle className="text-lg">Billing & Insurance</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-6 pt-6">
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="payer_type">Payer Type</Label>
                                            <Select value={payerType} onValueChange={(value) => setPayerType(value as 'cash' | 'insurance')}>
                                                <SelectTrigger id="payer_type"><SelectValue placeholder="Select payer type" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="cash">Cash Patient</SelectItem>
                                                    <SelectItem value="insurance">Insured Patient</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.payer_type} />
                                        </div>
                                    </div>

                                    {payerType === 'insurance' && (
                                        <div className="grid grid-cols-1 gap-4 rounded-lg border border-zinc-200 bg-zinc-50/50 p-4 shadow-inner sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-900/50">
                                            <div className="grid gap-2">
                                                <Label htmlFor="insurance_company_id">Insurance Company</Label>
                                                <Select value={companyId} onValueChange={setCompanyId}>
                                                    <SelectTrigger id="insurance_company_id"><SelectValue placeholder="Select company" /></SelectTrigger>
                                                    <SelectContent>
                                                        {companies.map((company) => (
                                                            <SelectItem key={company.id} value={company.id}>{company.name}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.insurance_company_id} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="insurance_package_id">Insurance Package</Label>
                                                <Select value={packageId} onValueChange={setPackageId}>
                                                    <SelectTrigger id="insurance_package_id"><SelectValue placeholder="Select package" /></SelectTrigger>
                                                    <SelectContent>
                                                        {filteredPackages.map((pkg) => (
                                                            <SelectItem key={pkg.id} value={pkg.id}>{pkg.name}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.insurance_package_id} />
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <div className="border-t pt-6">
                                <div className="flex items-center justify-start gap-3">
                                    <Button type="submit" disabled={processing} size="lg" className="min-w-[200px]">
                                        {processing ? <LoaderCircle className="mr-2 h-4 w-4 animate-spin" /> : <CheckCircle2 className="mr-2 h-4 w-4" />}
                                        Register Patient
                                    </Button>
                                    <Button variant="ghost" type="button" size="lg" asChild>
                                        <Link href="/patients">Cancel</Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
