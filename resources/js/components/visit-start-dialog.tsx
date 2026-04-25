import { SearchableSelect } from '@/components/searchable-select';
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
import { Form } from '@inertiajs/react';
import { Stethoscope } from 'lucide-react';
import { ReactNode, useEffect, useMemo, useState } from 'react';

const billingTypeOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'insurance', label: 'Insurance' },
];

const formatDoctorLabel = (doctor: { first_name: string; last_name: string }) =>
    `${doctor.first_name} ${doctor.last_name}`;

interface VisitStartDialogProps {
    patientId: string;
    patientName?: string;
    visitTypes: { value: string; label: string }[];
    clinics: { id: string; name?: string; clinic_name?: string }[];
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
    const [billingType, setBillingType] = useState<'cash' | 'insurance'>(
        'cash',
    );
    const [companyId, setCompanyId] = useState('');
    const [packageId, setPackageId] = useState('');

    const filteredPackages = useMemo(
        () => packages.filter((pkg) => pkg.insurance_company_id === companyId),
        [packages, companyId],
    );
    const clinicOptions = useMemo(
        () =>
            clinics.map((clinic) => ({
                value: clinic.id,
                label: clinic.name ?? clinic.clinic_name ?? 'Unnamed clinic',
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
                        value={redirectTo}
                    />

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor={`visit_type_${patientId}`}>
                                Visit Type
                            </Label>
                            <SearchableSelect
                                inputId={`visit_type_${patientId}`}
                                options={visitTypes}
                                value={visitType}
                                onValueChange={setVisitType}
                                placeholder="Select visit type"
                                emptyMessage="No visit types available."
                            />
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor={`clinic_id_${patientId}`}>
                                    Clinic
                                </Label>
                                <SearchableSelect
                                    inputId={`clinic_id_${patientId}`}
                                    options={clinicOptions}
                                    value={clinicId}
                                    onValueChange={setClinicId}
                                    placeholder="Select clinic"
                                    emptyMessage="No clinics available."
                                    allowClear
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`doctor_id_${patientId}`}>
                                    Doctor
                                </Label>
                                <SearchableSelect
                                    inputId={`doctor_id_${patientId}`}
                                    options={doctorOptions}
                                    value={doctorId}
                                    onValueChange={setDoctorId}
                                    placeholder="Select doctor"
                                    emptyMessage="No doctors available."
                                    allowClear
                                />
                            </div>
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor={`billing_type_${patientId}`}>
                                    Billing Type
                                </Label>
                                <SearchableSelect
                                    inputId={`billing_type_${patientId}`}
                                    options={billingTypeOptions}
                                    value={billingType}
                                    onValueChange={(value) =>
                                        setBillingType(
                                            value as 'cash' | 'insurance',
                                        )
                                    }
                                    placeholder="Select billing type"
                                />
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
                                    <Label
                                        htmlFor={`insurance_company_id_${patientId}`}
                                    >
                                        Insurer
                                    </Label>
                                    <SearchableSelect
                                        inputId={`insurance_company_id_${patientId}`}
                                        options={companyOptions}
                                        value={companyId}
                                        onValueChange={setCompanyId}
                                        placeholder="Select insurer"
                                        emptyMessage="No insurers available."
                                        allowClear
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`insurance_package_id_${patientId}`}
                                    >
                                        Package
                                    </Label>
                                    <SearchableSelect
                                        inputId={`insurance_package_id_${patientId}`}
                                        options={packageOptions}
                                        value={packageId}
                                        onValueChange={setPackageId}
                                        placeholder="Select package"
                                        emptyMessage="No packages available."
                                        allowClear
                                    />
                                </div>
                            </div>
                        ) : null}
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit">{submitLabel}</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
