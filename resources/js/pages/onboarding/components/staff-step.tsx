import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    type OnboardingDepartment,
    type OnboardingStaffPosition,
    type OnboardingStaffType,
} from '@/types/onboarding';
import { Form } from '@inertiajs/react';
import { LoaderCircle, Stethoscope } from 'lucide-react';
import type { Dispatch, SetStateAction } from 'react';

type StaffStepProps = {
    departments: OnboardingDepartment[];
    staffPositions: OnboardingStaffPosition[];
    staffTypes: OnboardingStaffType[];
    selectedDepartmentIds: string[];
    selectedStaffPositionId: string;
    selectedStaffType: string;
    setSelectedDepartmentIds: Dispatch<SetStateAction<string[]>>;
    setSelectedStaffPositionId: (value: string) => void;
    setSelectedStaffType: (value: string) => void;
};

export function StaffStep({
    departments,
    staffPositions,
    staffTypes,
    selectedDepartmentIds,
    selectedStaffPositionId,
    selectedStaffType,
    setSelectedDepartmentIds,
    setSelectedStaffPositionId,
    setSelectedStaffType,
}: StaffStepProps) {
    return (
        <Card className="rounded-3xl border-zinc-200 shadow-sm">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Stethoscope className="h-5 w-5" />
                    First staff member
                </CardTitle>
                <CardDescription>
                    Create the first operational staff profile for the
                    workspace. This person will be attached to the main branch
                    automatically.
                </CardDescription>
            </CardHeader>
            <Form
                method="post"
                action="/onboarding/staff"
                className="space-y-0"
            >
                {({ processing, errors }) => (
                    <>
                        <CardContent className="space-y-6">
                            {selectedDepartmentIds.map((id) => (
                                <input
                                    key={id}
                                    type="hidden"
                                    name="department_ids[]"
                                    value={id}
                                />
                            ))}
                            <input
                                type="hidden"
                                name="staff_position_id"
                                value={selectedStaffPositionId}
                            />
                            <input
                                type="hidden"
                                name="type"
                                value={selectedStaffType}
                            />
                            <input type="hidden" name="is_active" value="1" />

                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="staff_first_name">
                                        First name
                                    </Label>
                                    <Input
                                        id="staff_first_name"
                                        name="first_name"
                                        placeholder="e.g. Sarah"
                                    />
                                    <InputError message={errors.first_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="staff_last_name">
                                        Last name
                                    </Label>
                                    <Input
                                        id="staff_last_name"
                                        name="last_name"
                                        placeholder="e.g. Nansubuga"
                                    />
                                    <InputError message={errors.last_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="staff_middle_name">
                                        Middle name
                                    </Label>
                                    <Input
                                        id="staff_middle_name"
                                        name="middle_name"
                                        placeholder="Optional"
                                    />
                                    <InputError message={errors.middle_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="staff_email">Email</Label>
                                    <Input
                                        id="staff_email"
                                        name="email"
                                        type="email"
                                        placeholder="staff@hospital.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="staff_phone">Phone</Label>
                                    <Input
                                        id="staff_phone"
                                        name="phone"
                                        placeholder="+256700000000"
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="hire_date">Hire date</Label>
                                    <Input
                                        id="hire_date"
                                        name="hire_date"
                                        type="date"
                                    />
                                    <InputError message={errors.hire_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Staff type</Label>
                                    <Select
                                        value={selectedStaffType}
                                        onValueChange={setSelectedStaffType}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select staff type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {staffTypes.map((type) => (
                                                <SelectItem
                                                    key={type.value}
                                                    value={type.value}
                                                >
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Position</Label>
                                    <Select
                                        value={selectedStaffPositionId}
                                        onValueChange={
                                            setSelectedStaffPositionId
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select position" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {staffPositions.map((position) => (
                                                <SelectItem
                                                    key={position.id}
                                                    value={position.id}
                                                >
                                                    {position.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.staff_position_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="license_number">
                                        License number
                                    </Label>
                                    <Input
                                        id="license_number"
                                        name="license_number"
                                        placeholder="Optional"
                                    />
                                    <InputError
                                        message={errors.license_number}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="specialty">Specialty</Label>
                                    <Input
                                        id="specialty"
                                        name="specialty"
                                        placeholder="Optional"
                                    />
                                    <InputError message={errors.specialty} />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label>Departments</Label>
                                    <div className="rounded-2xl border p-4">
                                        <div className="grid gap-3 md:grid-cols-2">
                                            {departments.map((department) => (
                                                <label
                                                    key={department.id}
                                                    className="flex items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-3"
                                                >
                                                    <Checkbox
                                                        checked={selectedDepartmentIds.includes(
                                                            department.id,
                                                        )}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            setSelectedDepartmentIds(
                                                                checked === true
                                                                    ? [
                                                                          ...selectedDepartmentIds,
                                                                          department.id,
                                                                      ]
                                                                    : selectedDepartmentIds.filter(
                                                                          (
                                                                              id,
                                                                          ) =>
                                                                              id !==
                                                                              department.id,
                                                                      ),
                                                            )
                                                        }
                                                    />
                                                    <span className="text-sm text-zinc-700">
                                                        {department.name}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                    <InputError
                                        message={errors.department_ids}
                                    />
                                </div>
                            </div>
                        </CardContent>

                        <CardFooter className="flex flex-col items-stretch gap-3 border-t bg-zinc-50/70 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-zinc-600">
                                This final save marks onboarding complete and
                                opens the main workspace.
                            </p>
                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    selectedDepartmentIds.length === 0
                                }
                            >
                                {processing ? (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                ) : null}
                                Finish onboarding
                            </Button>
                        </CardFooter>
                    </>
                )}
            </Form>
        </Card>
    );
}
