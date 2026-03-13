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
import { CheckCircle2, LoaderCircle, UserRoundPlus } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: 'Register Patient', href: '/patients/create' },
];

export default function PatientCreate({
    countries,
    addresses,
    companies,
    packages,
    clinics,
    doctors,
    visitTypes,
    maritalStatusOptions,
    bloodGroupOptions,
    religionOptions,
    kinRelationshipOptions,
}: PatientCreatePageProps) {
    const [ageInputMode, setAgeInputMode] = useState<'dob' | 'age'>('dob');
    const [gender, setGender] = useState('unknown');
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
    const [billingType, setBillingType] = useState<'cash' | 'insurance'>('cash');
    const [companyId, setCompanyId] = useState('');
    const [packageId, setPackageId] = useState('');

    const filteredPackages = useMemo(
        () => packages.filter((pkg) => pkg.insurance_company_id === companyId),
        [packages, companyId],
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
                        <h1 className="text-2xl font-semibold">Register Patient & Start Visit</h1>
                        <p className="text-sm text-muted-foreground">
                            One transaction creates the patient, visit, and payer snapshot.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/patients">Back</Link>
                    </Button>
                </div>

                <Form
                    action="/patients"
                    method="post"
                    onSuccess={() => toast.success('Patient registered and visit started successfully.')}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="age_input_mode" value={ageInputMode} />
                            <input type="hidden" name="gender" value={gender} />
                            <input type="hidden" name="age_units" value={ageUnits} />
                            <input type="hidden" name="marital_status" value={maritalStatus} />
                            <input type="hidden" name="blood_group" value={bloodGroup} />
                            <input type="hidden" name="religion" value={religion} />
                            <input type="hidden" name="next_of_kin_relationship" value={kinRelationship} />
                            <input type="hidden" name="country_id" value={countryId} />
                            <input type="hidden" name="address_id" value={addressId} />
                            <input type="hidden" name="visit_type" value={visitType} />
                            <input type="hidden" name="clinic_id" value={clinicId} />
                            <input type="hidden" name="doctor_id" value={doctorId} />
                            <input type="hidden" name="billing_type" value={billingType} />
                            <input type="hidden" name="insurance_company_id" value={companyId} />
                            <input type="hidden" name="insurance_package_id" value={packageId} />
                            <input type="hidden" name="redirect_to" id="redirect_to" value="show" />

                            <Card>
                                <CardHeader><CardTitle>Patient Details</CardTitle></CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2"><Label htmlFor="first_name">First Name</Label><Input id="first_name" name="first_name" required autoFocus /><InputError message={errors.first_name} /></div>
                                    <div className="grid gap-2"><Label htmlFor="last_name">Last Name</Label><Input id="last_name" name="last_name" required /><InputError message={errors.last_name} /></div>
                                    <div className="grid gap-2"><Label htmlFor="middle_name">Middle Name</Label><Input id="middle_name" name="middle_name" /><InputError message={errors.middle_name} /></div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="gender">Gender</Label>
                                        <Select value={gender} onValueChange={setGender}>
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
                                        <div className="grid gap-2"><Label htmlFor="date_of_birth">Date of Birth</Label><Input id="date_of_birth" name="date_of_birth" type="date" /><InputError message={errors.date_of_birth} /></div>
                                    ) : (
                                        <>
                                            <div className="grid gap-2"><Label htmlFor="age">Age</Label><Input id="age" name="age" type="number" min="0" /><InputError message={errors.age} /></div>
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
                                    <div className="grid gap-2"><Label htmlFor="phone_number">Phone Number</Label><Input id="phone_number" name="phone_number" required /><InputError message={errors.phone_number} /></div>
                                    <div className="grid gap-2"><Label htmlFor="alternative_phone">Alternative Phone</Label><Input id="alternative_phone" name="alternative_phone" /><InputError message={errors.alternative_phone} /></div>
                                    <div className="grid gap-2"><Label htmlFor="email">Email</Label><Input id="email" name="email" type="email" /><InputError message={errors.email} /></div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="country_id">Country</Label>
                                        <Select value={countryId} onValueChange={setCountryId}>
                                            <SelectTrigger id="country_id"><SelectValue placeholder="Select country" /></SelectTrigger>
                                            <SelectContent>{countries.length > 0 ? countries.map((country) => <SelectItem key={country.id} value={country.id}>{country.country_name}</SelectItem>) : <SelectItem disabled value={''}>No countries available</SelectItem>}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="address_id">City / Address</Label>
                                        <Select value={addressId} onValueChange={setAddressId}>
                                            <SelectTrigger id="address_id"><SelectValue placeholder="Select address" /></SelectTrigger>
                                            <SelectContent>{addresses.length > 0 ? addresses.map((address) => <SelectItem key={address.id} value={address.id}>{address.city}{address.district ? `, ${address.district}` : ''}</SelectItem>) : <SelectItem  disabled value={''}>No addresses available</SelectItem>}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="marital_status">Marital Status</Label>
                                        <Select value={maritalStatus} onValueChange={setMaritalStatus}>
                                            <SelectTrigger id="marital_status"><SelectValue placeholder="Select marital status" /></SelectTrigger>
                                            <SelectContent>{maritalStatusOptions.length > 0 ? maritalStatusOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>) : <SelectItem disabled value={''}>No marital status options available</SelectItem>}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2"><Label htmlFor="occupation">Occupation</Label><Input id="occupation" name="occupation" /></div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="religion">Religion</Label>
                                        <Select value={religion} onValueChange={setReligion}>
                                            <SelectTrigger id="religion"><SelectValue placeholder="Select religion" /></SelectTrigger>
                                            <SelectContent>{religionOptions.length > 0 ? religionOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>) : <SelectItem disabled value={''}>No religion options available</SelectItem>}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="blood_group">Blood Group</Label>
                                        <Select value={bloodGroup} onValueChange={setBloodGroup}>
                                            <SelectTrigger id="blood_group"><SelectValue placeholder="Select blood group" /></SelectTrigger>
                                            <SelectContent>{bloodGroupOptions.length > 0 ? bloodGroupOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>) : <SelectItem disabled value={''}>No blood group options available</SelectItem>}</SelectContent>
                                        </Select>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader><CardTitle>Next of Kin</CardTitle></CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2"><Label htmlFor="next_of_kin_name">Name</Label><Input id="next_of_kin_name" name="next_of_kin_name" /></div>
                                    <div className="grid gap-2"><Label htmlFor="next_of_kin_phone">Phone</Label><Input id="next_of_kin_phone" name="next_of_kin_phone" /></div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="next_of_kin_relationship">Relationship</Label>
                                        <Select value={kinRelationship} onValueChange={setKinRelationship}>
                                            <SelectTrigger id="next_of_kin_relationship"><SelectValue placeholder="Select relationship" /></SelectTrigger>
                                            <SelectContent>{kinRelationshipOptions.length > 0 ? kinRelationshipOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>) : <SelectItem disabled value={''}>No relationship options available</SelectItem>}</SelectContent>
                                        </Select>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader><CardTitle>Visit & Billing</CardTitle></CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="visit_type">Visit Type</Label>
                                        <Select value={visitType} onValueChange={setVisitType}>
                                            <SelectTrigger id="visit_type"><SelectValue placeholder="Select visit type" /></SelectTrigger>
                                            <SelectContent>{visitTypes.length > 0 ? visitTypes.map((type) => <SelectItem key={type.value} value={type.value}>{type.label}</SelectItem>) : <SelectItem disabled value="none">No visit types available</SelectItem>}</SelectContent>
                                        </Select>
                                        <InputError message={errors.visit_type} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="clinic_id">Clinic</Label>
                                        <Select value={clinicId} onValueChange={setClinicId}>
                                            <SelectTrigger id="clinic_id"><SelectValue placeholder="Select clinic" /></SelectTrigger>
                                            <SelectContent>{clinics.length > 0 ? clinics.map((clinic) => <SelectItem key={clinic.id} value={clinic.id}>{clinic.clinic_name}</SelectItem>) : <SelectItem disabled value="none">No clinics available</SelectItem>}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="doctor_id">Doctor</Label>
                                        <Select value={doctorId} onValueChange={setDoctorId}>
                                            <SelectTrigger id="doctor_id"><SelectValue placeholder="Select doctor" /></SelectTrigger>
                                            <SelectContent>{doctors.length > 0 ? doctors.map((doctor) => <SelectItem key={doctor.id} value={doctor.id}>{doctor.first_name} {doctor.last_name}</SelectItem>) : <SelectItem disabled value="none">No doctors available</SelectItem>}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="billing_type">Billing Type</Label>
                                        <Select value={billingType} onValueChange={(value) => setBillingType(value as 'cash' | 'insurance')}>
                                            <SelectTrigger id="billing_type"><SelectValue placeholder="Select billing type" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="cash">Cash</SelectItem>
                                                <SelectItem value="insurance">Insurance</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.billing_type} />
                                    </div>
                                    {billingType === 'insurance' && (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="insurance_company_id">Insurer</Label>
                                                <Select value={companyId} onValueChange={setCompanyId}>
                                                    <SelectTrigger id="insurance_company_id"><SelectValue placeholder="Select insurer" /></SelectTrigger>
                                                    <SelectContent>{companies.length > 0 ? companies.map((company) => <SelectItem key={company.id} value={company.id}>{company.name}</SelectItem>) : <SelectItem disabled value="none">No insurers available</SelectItem>}</SelectContent>
                                                </Select>
                                                <InputError message={errors.insurance_company_id} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="insurance_package_id">Package</Label>
                                                <Select value={packageId} onValueChange={setPackageId}>
                                                    <SelectTrigger id="insurance_package_id"><SelectValue placeholder="Select package" /></SelectTrigger>
                                                    <SelectContent>{filteredPackages.length > 0 ? filteredPackages.map((pkg) => <SelectItem key={pkg.id} value={pkg.id}>{pkg.name}</SelectItem>) : <SelectItem disabled value="none">No packages available</SelectItem>}</SelectContent>
                                                </Select>
                                                <InputError message={errors.insurance_package_id} />
                                            </div>
                                        </>
                                    )}
                                    <div className="flex items-center gap-2 pt-8">
                                        <input type="checkbox" id="is_emergency" name="is_emergency" value="1" className="h-4 w-4 rounded border-gray-300" />
                                        <Label htmlFor="is_emergency" className="font-normal">Emergency Visit</Label>
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    onClick={() => {
                                        const input = document.getElementById('redirect_to') as HTMLInputElement | null;
                                        if (input) input.value = 'show';
                                    }}
                                >
                                    {processing ? <LoaderCircle className="mr-2 h-4 w-4 animate-spin" /> : <UserRoundPlus className="mr-2 h-4 w-4" />}
                                    Register & Open Profile
                                </Button>
                                <Button
                                    type="submit"
                                    variant="secondary"
                                    disabled={processing}
                                    onClick={() => {
                                        const input = document.getElementById('redirect_to') as HTMLInputElement | null;
                                        if (input) input.value = 'list';
                                    }}
                                >
                                    {processing ? <LoaderCircle className="mr-2 h-4 w-4 animate-spin" /> : <CheckCircle2 className="mr-2 h-4 w-4" />}
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
