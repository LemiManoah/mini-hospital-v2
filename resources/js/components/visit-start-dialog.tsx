import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Form } from '@inertiajs/react';
import { Stethoscope } from 'lucide-react';
import { ReactNode, useEffect, useMemo, useState } from 'react';

interface VisitStartDialogProps {
    patientId: string;
    patientName?: string;
    visitTypes: { value: string; label: string }[];
    clinics: { id: string; name: string }[];
    doctors: { id: string; first_name: string; last_name: string }[];
    companies: { id: string; name: string }[];
    packages: { id: string; name: string; insurance_company_id: string }[];
    disabled?: boolean;
    redirectTo?: 'patient' | 'visit' | 'index';
    trigger?: ReactNode;
    title?: string;
    description?: string;
    submitLabel?: string;
    onSuccess?: () => void;
}

export default function VisitStartDialog({
    patientId,
    patientName,
    visitTypes,
    clinics,
    doctors,
    companies,
    packages,
    disabled = false,
    redirectTo = 'patient',
    trigger,
    title = 'Start Visit',
    description,
    submitLabel = 'Start Visit',
    onSuccess,
}: VisitStartDialogProps) {
    const [open, setOpen] = useState(false);
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
        setPackageId('');
    }, [companyId]);

    const dialogDescription =
        description ??
        (patientName
            ? `Create a new visit and payer snapshot for ${patientName}.`
            : 'Create a new visit and payer snapshot for this patient.');

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger || (
                    <Button disabled={disabled}>
                        <Stethoscope className="mr-2 h-4 w-4" />
                        {submitLabel}
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{dialogDescription}</DialogDescription>
                </DialogHeader>
                <Form
                    method="post"
                    action={`/patients/${patientId}/visits`}
                    onSuccess={() => {
                        setOpen(false);
                        onSuccess?.();
                    }}
                >
                    <input type="hidden" name="visit_type" value={visitType} />
                    <input type="hidden" name="clinic_id" value={clinicId} />
                    <input type="hidden" name="doctor_id" value={doctorId} />
                    <input type="hidden" name="billing_type" value={billingType} />
                    <input type="hidden" name="insurance_company_id" value={companyId} />
                    <input type="hidden" name="insurance_package_id" value={packageId} />
                    <input type="hidden" name="redirect_to" value={redirectTo} />

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor={`visit_type_${patientId}`}>Visit Type</Label>
                            <Select value={visitType} onValueChange={setVisitType}>
                                <SelectTrigger id={`visit_type_${patientId}`}>
                                    <SelectValue placeholder="Select visit type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {visitTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor={`clinic_id_${patientId}`}>Clinic</Label>
                                <Select value={clinicId} onValueChange={setClinicId}>
                                    <SelectTrigger id={`clinic_id_${patientId}`}>
                                        <SelectValue placeholder="Select clinic" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {clinics.map((clinic) => (
                                            <SelectItem key={clinic.id} value={clinic.id}>
                                                {clinic.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`doctor_id_${patientId}`}>Doctor</Label>
                                <Select value={doctorId} onValueChange={setDoctorId}>
                                    <SelectTrigger id={`doctor_id_${patientId}`}>
                                        <SelectValue placeholder="Select doctor" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {doctors.map((doctor) => (
                                            <SelectItem key={doctor.id} value={doctor.id}>
                                                {doctor.first_name} {doctor.last_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor={`billing_type_${patientId}`}>Billing Type</Label>
                                <Select
                                    value={billingType}
                                    onValueChange={(value) =>
                                        setBillingType(value as 'cash' | 'insurance')
                                    }
                                >
                                    <SelectTrigger id={`billing_type_${patientId}`}>
                                        <SelectValue placeholder="Select billing type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="cash">Cash</SelectItem>
                                        <SelectItem value="insurance">Insurance</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-center gap-2 pt-8">
                                <input
                                    type="checkbox"
                                    id={`is_emergency_${patientId}`}
                                    name="is_emergency"
                                    value="1"
                                    className="h-4 w-4 rounded border-gray-300"
                                />
                                <Label
                                    htmlFor={`is_emergency_${patientId}`}
                                    className="font-normal"
                                >
                                    Emergency Visit
                                </Label>
                            </div>
                        </div>

                        {billingType === 'insurance' ? (
                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor={`insurance_company_id_${patientId}`}>
                                        Insurer
                                    </Label>
                                    <Select value={companyId} onValueChange={setCompanyId}>
                                        <SelectTrigger id={`insurance_company_id_${patientId}`}>
                                            <SelectValue placeholder="Select insurer" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {companies.map((company) => (
                                                <SelectItem key={company.id} value={company.id}>
                                                    {company.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`insurance_package_id_${patientId}`}>
                                        Package
                                    </Label>
                                    <Select value={packageId} onValueChange={setPackageId}>
                                        <SelectTrigger id={`insurance_package_id_${patientId}`}>
                                            <SelectValue placeholder="Select package" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filteredPackages.map((pkg) => (
                                                <SelectItem key={pkg.id} value={pkg.id}>
                                                    {pkg.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        ) : null}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="submit">{submitLabel}</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
