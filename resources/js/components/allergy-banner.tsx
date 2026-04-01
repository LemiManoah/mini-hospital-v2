import { AlertCircle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { type PatientVisit } from '@/types/patient';

interface Allergy {
    id: string;
    allergen_name: string;
    severity: string;
    reaction?: string | null;
}

export function AllergyBanner({ 
    allergies 
}: { 
    allergies: Allergy[] | null | undefined 
}) {
    if (!allergies || allergies.length === 0) return null;

    return (
        <Alert variant="destructive" className="bg-red-50 border-red-200 text-red-900 shadow-sm">
            <AlertCircle className="h-5 w-5 !text-red-600" />
            <AlertTitle className="font-bold text-red-800 uppercase tracking-tight text-xs">Patient Allergies Warning</AlertTitle>
            <AlertDescription className="font-medium">
                This patient has documented allergies to: <span className="font-bold">{allergies.map(a => a.allergen_name).join(', ')}</span>.
                Please review carefully before prescribing or administering medication.
            </AlertDescription>
        </Alert>
    );
}
