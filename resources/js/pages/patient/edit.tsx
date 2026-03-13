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
import { type PatientEditPageProps } from '@/types/patient';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function PatientEdit({
    patient,
    countries,
    addresses,
    maritalStatusOptions,
    bloodGroupOptions,
    religionOptions,
    kinRelationshipOptions,
}: PatientEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Patients', href: '/patients' },
        { title: `${patient.first_name} ${patient.last_name}`, href: `/patients/${patient.id}` },
        { title: 'Edit', href: `/patients/${patient.id}/edit` },
    ];

    const [ageInputMode, setAgeInputMode] = useState<'dob' | 'age'>(patient.date_of_birth ? 'dob' : 'age');
    const [gender, setGender] = useState(patient.gender || 'unknown');
    const [ageUnits, setAgeUnits] = useState<'year' | 'month' | 'day'>(patient.age_units || 'year');
    const [maritalStatus, setMaritalStatus] = useState(patient.marital_status || '');
    const [bloodGroup, setBloodGroup] = useState(patient.blood_group || '');
    const [religion, setReligion] = useState(patient.religion || '');
    const [kinRelationship, setKinRelationship] = useState(patient.next_of_kin_relationship || '');
    const [countryId, setCountryId] = useState(patient.country_id || '');
    const [addressId, setAddressId] = useState(patient.address_id || '');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Patient - ${patient.first_name}`} />

            <div className="m-4 max-w-5xl space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Edit Patient</h1>
                        <p className="text-sm text-muted-foreground">Billing now lives on each visit, so this page only updates demographics and contacts.</p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={`/patients/${patient.id}`}>Back</Link>
                    </Button>
                </div>

                <Form
                    action={`/patients/${patient.id}`}
                    method="put"
                    onSuccess={() => toast.success('Patient updated successfully.')}
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

                            <Card>
                                <CardHeader><CardTitle>Patient Details</CardTitle></CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2"><Label htmlFor="first_name">First Name</Label><Input id="first_name" name="first_name" defaultValue={patient.first_name} required /><InputError message={errors.first_name} /></div>
                                    <div className="grid gap-2"><Label htmlFor="last_name">Last Name</Label><Input id="last_name" name="last_name" defaultValue={patient.last_name} required /><InputError message={errors.last_name} /></div>
                                    <div className="grid gap-2"><Label htmlFor="middle_name">Middle Name</Label><Input id="middle_name" name="middle_name" defaultValue={patient.middle_name || ''} /></div>
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
                                        <div className="grid gap-2"><Label htmlFor="date_of_birth">Date of Birth</Label><Input id="date_of_birth" name="date_of_birth" type="date" defaultValue={patient.date_of_birth ? new Date(patient.date_of_birth).toISOString().split('T')[0] : ''} /><InputError message={errors.date_of_birth} /></div>
                                    ) : (
                                        <>
                                            <div className="grid gap-2"><Label htmlFor="age">Age</Label><Input id="age" name="age" type="number" min="0" defaultValue={patient.age || ''} /><InputError message={errors.age} /></div>
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
                                    <div className="grid gap-2"><Label htmlFor="phone_number">Phone Number</Label><Input id="phone_number" name="phone_number" defaultValue={patient.phone_number} required /><InputError message={errors.phone_number} /></div>
                                    <div className="grid gap-2"><Label htmlFor="alternative_phone">Alternative Phone</Label><Input id="alternative_phone" name="alternative_phone" defaultValue={patient.alternative_phone || ''} /></div>
                                    <div className="grid gap-2"><Label htmlFor="email">Email</Label><Input id="email" name="email" type="email" defaultValue={patient.email || ''} /><InputError message={errors.email} /></div>
                                    <div className="grid gap-2"><Label htmlFor="country_id">Country</Label><Select value={countryId} onValueChange={setCountryId}><SelectTrigger id="country_id"><SelectValue placeholder="Select country" /></SelectTrigger><SelectContent>{countries.map((country) => <SelectItem key={country.id} value={country.id}>{country.country_name}</SelectItem>)}</SelectContent></Select></div>
                                    <div className="grid gap-2"><Label htmlFor="address_id">City / Address</Label><Select value={addressId} onValueChange={setAddressId}><SelectTrigger id="address_id"><SelectValue placeholder="Select address" /></SelectTrigger><SelectContent>{addresses.map((address) => <SelectItem key={address.id} value={address.id}>{address.city}{address.district ? `, ${address.district}` : ''}</SelectItem>)}</SelectContent></Select></div>
                                    <div className="grid gap-2"><Label htmlFor="marital_status">Marital Status</Label><Select value={maritalStatus} onValueChange={setMaritalStatus}><SelectTrigger id="marital_status"><SelectValue placeholder="Select marital status" /></SelectTrigger><SelectContent>{maritalStatusOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent></Select></div>
                                    <div className="grid gap-2"><Label htmlFor="occupation">Occupation</Label><Input id="occupation" name="occupation" defaultValue={patient.occupation || ''} /></div>
                                    <div className="grid gap-2"><Label htmlFor="religion">Religion</Label><Select value={religion} onValueChange={setReligion}><SelectTrigger id="religion"><SelectValue placeholder="Select religion" /></SelectTrigger><SelectContent>{religionOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent></Select></div>
                                    <div className="grid gap-2"><Label htmlFor="blood_group">Blood Group</Label><Select value={bloodGroup} onValueChange={setBloodGroup}><SelectTrigger id="blood_group"><SelectValue placeholder="Select blood group" /></SelectTrigger><SelectContent>{bloodGroupOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent></Select></div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader><CardTitle>Next of Kin</CardTitle></CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2"><Label htmlFor="next_of_kin_name">Name</Label><Input id="next_of_kin_name" name="next_of_kin_name" defaultValue={patient.next_of_kin_name || ''} /></div>
                                    <div className="grid gap-2"><Label htmlFor="next_of_kin_phone">Phone</Label><Input id="next_of_kin_phone" name="next_of_kin_phone" defaultValue={patient.next_of_kin_phone || ''} /></div>
                                    <div className="grid gap-2"><Label htmlFor="next_of_kin_relationship">Relationship</Label><Select value={kinRelationship} onValueChange={setKinRelationship}><SelectTrigger id="next_of_kin_relationship"><SelectValue placeholder="Select relationship" /></SelectTrigger><SelectContent>{kinRelationshipOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent></Select></div>
                                </CardContent>
                            </Card>

                            <Button type="submit" disabled={processing}>
                                {processing ? <LoaderCircle className="mr-2 h-4 w-4 animate-spin" /> : null}
                                Save Changes
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}

