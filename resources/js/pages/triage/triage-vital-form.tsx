import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type UnitOption = {
    value: string;
    label: string;
};

export default function TriageVitalForm({
    errors,
    temperatureUnit,
    setTemperatureUnit,
    bloodGlucoseUnit,
    setBloodGlucoseUnit,
    temperatureUnits,
    bloodGlucoseUnits,
}: {
    errors: Partial<Record<string, string>>;
    temperatureUnit: string;
    setTemperatureUnit: (value: string) => void;
    bloodGlucoseUnit: string;
    setBloodGlucoseUnit: (value: string) => void;
    temperatureUnits: UnitOption[];
    bloodGlucoseUnits: UnitOption[];
}) {
    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <div className="grid gap-2">
                <Label htmlFor="temperature">Temperature</Label>
                <div className="flex gap-2">
                    <Input
                        id="temperature"
                        name="temperature"
                        type="number"
                        step="0.1"
                        placeholder="36.1 to 37.2 C"
                        className="flex-1"
                    />
                    <Select
                        value={temperatureUnit}
                        onValueChange={setTemperatureUnit}
                    >
                        <SelectTrigger className="w-32 shrink-0">
                            <SelectValue placeholder="Unit" />
                        </SelectTrigger>
                        <SelectContent>
                            {temperatureUnits.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <InputError message={errors.temperature} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="pulse_rate">Pulse Rate</Label>
                <Input
                    id="pulse_rate"
                    name="pulse_rate"
                    type="number"
                    placeholder="60 to 100 bpm"
                />
                <InputError message={errors.pulse_rate} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="respiratory_rate">Respiratory Rate</Label>
                <Input
                    id="respiratory_rate"
                    name="respiratory_rate"
                    type="number"
                    placeholder="12 to 20 /min"
                />
                <InputError message={errors.respiratory_rate} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="pain_score">Pain Score</Label>
                <Input
                    id="pain_score"
                    name="pain_score"
                    type="number"
                    min={0}
                    max={10}
                    placeholder="0 to 10"
                />
                <InputError message={errors.pain_score} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="systolic_bp">Systolic BP</Label>
                <Input
                    id="systolic_bp"
                    name="systolic_bp"
                    type="number"
                    placeholder="90 to 120 mmHg"
                />
                <InputError message={errors.systolic_bp} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="diastolic_bp">Diastolic BP</Label>
                <Input
                    id="diastolic_bp"
                    name="diastolic_bp"
                    type="number"
                    placeholder="60 to 80 mmHg"
                />
                <InputError message={errors.diastolic_bp} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="oxygen_saturation">SpO2</Label>
                <Input
                    id="oxygen_saturation"
                    name="oxygen_saturation"
                    type="number"
                    step="0.1"
                    placeholder="95 to 100%"
                />
                <InputError message={errors.oxygen_saturation} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="oxygen_delivery_method">
                    Oxygen Delivery Method
                </Label>
                <Input
                    id="oxygen_delivery_method"
                    name="oxygen_delivery_method"
                    placeholder="Nasal cannula, mask, or room air"
                />
                <InputError message={errors.oxygen_delivery_method} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="oxygen_flow_rate">Oxygen Flow Rate</Label>
                <Input
                    id="oxygen_flow_rate"
                    name="oxygen_flow_rate"
                    type="number"
                    step="0.1"
                    placeholder="0 to 15 L/min"
                />
                <InputError message={errors.oxygen_flow_rate} />
            </div>

            <div className="grid gap-2">
                <Label>Oxygen Support</Label>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="on_supplemental_oxygen"
                        value="1"
                    />
                    On supplemental oxygen
                </label>
                <InputError message={errors.on_supplemental_oxygen} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="blood_glucose">Blood Glucose</Label>
                <div className="flex gap-2">
                    <Input
                        id="blood_glucose"
                        name="blood_glucose"
                        type="number"
                        step="0.1"
                        placeholder="70 to 140 mg/dL"
                        className="flex-1"
                    />
                    <Select
                        value={bloodGlucoseUnit}
                        onValueChange={setBloodGlucoseUnit}
                    >
                        <SelectTrigger className="w-32 shrink-0">
                            <SelectValue placeholder="Unit" />
                        </SelectTrigger>
                        <SelectContent>
                            {bloodGlucoseUnits.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <InputError message={errors.blood_glucose} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="height_cm">Height</Label>
                <Input
                    id="height_cm"
                    name="height_cm"
                    type="number"
                    step="0.1"
                    placeholder="45 to 220 cm"
                />
                <InputError message={errors.height_cm} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="weight_kg">Weight</Label>
                <Input
                    id="weight_kg"
                    name="weight_kg"
                    type="number"
                    step="0.1"
                    placeholder="2 to 300 kg"
                />
                <InputError message={errors.weight_kg} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="head_circumference_cm">
                    Head Circumference
                </Label>
                <Input
                    id="head_circumference_cm"
                    name="head_circumference_cm"
                    type="number"
                    step="0.1"
                    placeholder="30 to 55 cm"
                />
                <InputError message={errors.head_circumference_cm} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="chest_circumference_cm">
                    Chest Circumference
                </Label>
                <Input
                    id="chest_circumference_cm"
                    name="chest_circumference_cm"
                    type="number"
                    step="0.1"
                    placeholder="30 to 120 cm"
                />
                <InputError message={errors.chest_circumference_cm} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="muac_cm">MUAC</Label>
                <Input
                    id="muac_cm"
                    name="muac_cm"
                    type="number"
                    step="0.1"
                    placeholder="11.5 cm and above"
                />
                <InputError message={errors.muac_cm} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="capillary_refill">Capillary Refill</Label>
                <Input
                    id="capillary_refill"
                    name="capillary_refill"
                    placeholder="Less than 2 seconds"
                />
                <InputError message={errors.capillary_refill} />
            </div>
        </div>
    );
}
