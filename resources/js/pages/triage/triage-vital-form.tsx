import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Form } from '@inertiajs/react';
import { type ReactNode } from 'react';

const vitalFieldHints = {
    temperature: {
        range: '36.1 to 37.2 C',
        note: 'Typical adult oral range',
    },
    pulse_rate: {
        range: '60 to 100 bpm',
        note: 'Resting adult range',
    },
    respiratory_rate: {
        range: '12 to 20 /min',
        note: 'Resting adult range',
    },
    systolic_bp: {
        range: '90 to 120 mmHg',
        note: 'Interpret alongside diastolic pressure',
    },
    diastolic_bp: {
        range: '60 to 80 mmHg',
        note: 'Interpret alongside systolic pressure',
    },
    oxygen_saturation: {
        range: '95 to 100%',
        note: 'Lower values may be expected with respiratory disease',
    },
    oxygen_delivery_method: {
        range: 'Nasal cannula, mask, or room air',
        note: 'Document the support method in use',
    },
    oxygen_flow_rate: {
        range: '0 to 15 L/min',
        note: 'Only needed when oxygen support is being used',
    },
    blood_glucose: {
        range: '70 to 140 mg/dL',
        note: 'About 3.9 to 7.8 mmol/L randomly',
    },
    pain_score: {
        range: '0 to 10',
        note: '0 none, 10 worst pain imaginable',
    },
    height_cm: {
        range: '45 to 220 cm',
        note: 'Used with weight to estimate BMI',
    },
    weight_kg: {
        range: '2 to 300 kg',
        note: 'Use a recent measured weight when possible',
    },
    head_circumference_cm: {
        range: '30 to 55 cm',
        note: 'Most useful in pediatric assessments',
    },
    chest_circumference_cm: {
        range: '30 to 120 cm',
        note: 'Capture when clinically indicated',
    },
    muac_cm: {
        range: '11.5 cm and above',
        note: 'Helpful for nutrition screening',
    },
    capillary_refill: {
        range: 'Less than 2 seconds',
        note: 'Record the observed refill finding',
    },
} as const;

type VitalFieldHintKey = keyof typeof vitalFieldHints;

function VitalField({
    label,
    hintKey,
    error,
    children,
    className = '',
}: {
    label: string;
    hintKey: VitalFieldHintKey;
    error?: string;
    children: ReactNode;
    className?: string;
}) {
    const hint = vitalFieldHints[hintKey];

    return (
        <div
            className={`rounded-xl border border-border/70 bg-muted/20 p-4 ${className}`}
        >
            <div className="mb-3">
                <Label className="text-sm font-medium">{label}</Label>
                <p className="mt-1 text-xs leading-5 text-muted-foreground">
                    <span className="font-medium text-foreground/80">
                        {hint.range}
                    </span>
                    {' · '}
                    {hint.note}
                </p>
            </div>
            <div className="grid gap-2">
                {children}
                <InputError message={error} />
            </div>
        </div>
    );
}

type UnitOption = {
    value: string;
    label: string;
};

export default function TriageVitalForm({
    visitId,
    temperatureUnit,
    setTemperatureUnit,
    bloodGlucoseUnit,
    setBloodGlucoseUnit,
    temperatureUnits,
    bloodGlucoseUnits,
}: {
    visitId: string;
    temperatureUnit: string;
    setTemperatureUnit: (value: string) => void;
    bloodGlucoseUnit: string;
    setBloodGlucoseUnit: (value: string) => void;
    temperatureUnits: UnitOption[];
    bloodGlucoseUnits: UnitOption[];
}) {
    return (
        <Form method="post" action={`/visits/${visitId}/vitals`}>
            {({ processing, errors }) => (
                <div className="space-y-6 rounded-xl border p-4 md:p-5">
                    <input type="hidden" name="redirect_to" value="triage" />
                    <input
                        type="hidden"
                        name="temperature_unit"
                        value={temperatureUnit}
                    />
                    <input
                        type="hidden"
                        name="blood_glucose_unit"
                        value={bloodGlucoseUnit}
                    />

                    <div className="rounded-xl border border-dashed bg-muted/20 p-4 text-sm text-muted-foreground">
                        Reference hints now sit on each field so nurses can
                        capture vitals without cross-checking a separate card.
                    </div>

                    <div className="space-y-4">
                        <div>
                            <h3 className="font-medium">Core Observations</h3>
                            <p className="text-sm text-muted-foreground">
                                Temperature, pulse, breathing, and pain at first
                                contact.
                            </p>
                        </div>
                        <div className="grid gap-4 lg:grid-cols-2">
                            <VitalField
                                label="Temperature"
                                hintKey="temperature"
                                error={errors.temperature}
                            >
                                <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_180px]">
                                    <Input
                                        id="temperature"
                                        name="temperature"
                                        type="number"
                                        step="0.1"
                                        placeholder="e.g. 37.0"
                                    />
                                    <div className="grid gap-2">
                                        <Label>Unit</Label>
                                        <Select
                                            value={temperatureUnit}
                                            onValueChange={setTemperatureUnit}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select unit" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {temperatureUnits.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </VitalField>

                            <VitalField
                                label="Pulse Rate"
                                hintKey="pulse_rate"
                                error={errors.pulse_rate}
                            >
                                <Input
                                    id="pulse_rate"
                                    name="pulse_rate"
                                    type="number"
                                    placeholder="e.g. 82"
                                />
                            </VitalField>

                            <VitalField
                                label="Respiratory Rate"
                                hintKey="respiratory_rate"
                                error={errors.respiratory_rate}
                            >
                                <Input
                                    id="respiratory_rate"
                                    name="respiratory_rate"
                                    type="number"
                                    placeholder="e.g. 18"
                                />
                            </VitalField>

                            <VitalField
                                label="Pain Score"
                                hintKey="pain_score"
                                error={errors.pain_score}
                            >
                                <Input
                                    id="pain_score"
                                    name="pain_score"
                                    type="number"
                                    min={0}
                                    max={10}
                                    placeholder="0 to 10"
                                />
                            </VitalField>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <h3 className="font-medium">
                                Circulation And Oxygenation
                            </h3>
                            <p className="text-sm text-muted-foreground">
                                Pressure, perfusion, oxygen support, and
                                respiratory stability.
                            </p>
                        </div>
                        <div className="grid gap-4 lg:grid-cols-2">
                            <VitalField
                                label="Systolic Blood Pressure"
                                hintKey="systolic_bp"
                                error={errors.systolic_bp}
                            >
                                <Input
                                    id="systolic_bp"
                                    name="systolic_bp"
                                    type="number"
                                    placeholder="e.g. 120"
                                />
                            </VitalField>

                            <VitalField
                                label="Diastolic Blood Pressure"
                                hintKey="diastolic_bp"
                                error={errors.diastolic_bp}
                            >
                                <Input
                                    id="diastolic_bp"
                                    name="diastolic_bp"
                                    type="number"
                                    placeholder="e.g. 80"
                                />
                            </VitalField>

                            <VitalField
                                label="SpO2"
                                hintKey="oxygen_saturation"
                                error={errors.oxygen_saturation}
                            >
                                <Input
                                    id="oxygen_saturation"
                                    name="oxygen_saturation"
                                    type="number"
                                    step="0.1"
                                    placeholder="e.g. 98"
                                />
                            </VitalField>

                            <VitalField
                                label="Oxygen Delivery Method"
                                hintKey="oxygen_delivery_method"
                                error={errors.oxygen_delivery_method}
                            >
                                <Input
                                    id="oxygen_delivery_method"
                                    name="oxygen_delivery_method"
                                    placeholder="Room air, nasal cannula, mask..."
                                />
                            </VitalField>

                            <VitalField
                                label="Oxygen Flow Rate"
                                hintKey="oxygen_flow_rate"
                                error={errors.oxygen_flow_rate}
                            >
                                <Input
                                    id="oxygen_flow_rate"
                                    name="oxygen_flow_rate"
                                    type="number"
                                    step="0.1"
                                    placeholder="e.g. 2"
                                />
                            </VitalField>

                            <div className="rounded-xl border border-border/70 bg-muted/20 p-4">
                                <Label className="text-sm font-medium">
                                    Oxygen Support
                                </Label>
                                <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                    Mark this when the patient is receiving
                                    supplemental oxygen at capture time.
                                </p>
                                <label className="mt-4 flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="on_supplemental_oxygen"
                                        value="1"
                                    />
                                    On supplemental oxygen
                                </label>
                                <InputError
                                    message={errors.on_supplemental_oxygen}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <h3 className="font-medium">
                                Metabolic And Body Measurements
                            </h3>
                            <p className="text-sm text-muted-foreground">
                                Glucose and anthropometry for dosing, nutrition,
                                and pediatric screening.
                            </p>
                        </div>
                        <div className="grid gap-4 lg:grid-cols-2">
                            <VitalField
                                label="Blood Glucose"
                                hintKey="blood_glucose"
                                error={errors.blood_glucose}
                            >
                                <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_180px]">
                                    <Input
                                        id="blood_glucose"
                                        name="blood_glucose"
                                        type="number"
                                        step="0.1"
                                        placeholder="e.g. 110"
                                    />
                                    <div className="grid gap-2">
                                        <Label>Unit</Label>
                                        <Select
                                            value={bloodGlucoseUnit}
                                            onValueChange={setBloodGlucoseUnit}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select unit" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {bloodGlucoseUnits.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </VitalField>

                            <VitalField
                                label="Height"
                                hintKey="height_cm"
                                error={errors.height_cm}
                            >
                                <Input
                                    id="height_cm"
                                    name="height_cm"
                                    type="number"
                                    step="0.1"
                                    placeholder="Height in cm"
                                />
                            </VitalField>

                            <VitalField
                                label="Weight"
                                hintKey="weight_kg"
                                error={errors.weight_kg}
                            >
                                <Input
                                    id="weight_kg"
                                    name="weight_kg"
                                    type="number"
                                    step="0.1"
                                    placeholder="Weight in kg"
                                />
                            </VitalField>

                            <VitalField
                                label="Head Circumference"
                                hintKey="head_circumference_cm"
                                error={errors.head_circumference_cm}
                            >
                                <Input
                                    id="head_circumference_cm"
                                    name="head_circumference_cm"
                                    type="number"
                                    step="0.1"
                                    placeholder="Head circumference in cm"
                                />
                            </VitalField>

                            <VitalField
                                label="Chest Circumference"
                                hintKey="chest_circumference_cm"
                                error={errors.chest_circumference_cm}
                            >
                                <Input
                                    id="chest_circumference_cm"
                                    name="chest_circumference_cm"
                                    type="number"
                                    step="0.1"
                                    placeholder="Chest circumference in cm"
                                />
                            </VitalField>

                            <VitalField
                                label="MUAC"
                                hintKey="muac_cm"
                                error={errors.muac_cm}
                            >
                                <Input
                                    id="muac_cm"
                                    name="muac_cm"
                                    type="number"
                                    step="0.1"
                                    placeholder="MUAC in cm"
                                />
                            </VitalField>

                            <VitalField
                                label="Capillary Refill"
                                hintKey="capillary_refill"
                                error={errors.capillary_refill}
                                className="lg:col-span-2"
                            >
                                <Input
                                    id="capillary_refill"
                                    name="capillary_refill"
                                    placeholder="e.g. < 2 sec"
                                />
                            </VitalField>
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save Vitals'}
                        </Button>
                    </div>
                </div>
            )}
        </Form>
    );
}
