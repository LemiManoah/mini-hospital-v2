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
    emptyOnboardingDepartment,
    type OnboardingDepartmentDraft,
} from '@/types/onboarding';
import { Form } from '@inertiajs/react';
import {
    CheckCircle2,
    LoaderCircle,
    Plus,
    Stethoscope,
    Trash2,
} from 'lucide-react';
import type { Dispatch, SetStateAction } from 'react';

type DepartmentsStepProps = {
    departmentRows: OnboardingDepartmentDraft[];
    setDepartmentRows: Dispatch<SetStateAction<OnboardingDepartmentDraft[]>>;
};

export function DepartmentsStep({
    departmentRows,
    setDepartmentRows,
}: DepartmentsStepProps) {
    return (
        <Card className="rounded-3xl border-zinc-200 shadow-sm">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Stethoscope className="h-5 w-5" />
                    Department bootstrap
                </CardTitle>
                <CardDescription>
                    Add the essential departments the team needs on day one.
                    More can be added later.
                </CardDescription>
            </CardHeader>
            <Form
                method="post"
                action="/onboarding/departments"
                className="space-y-0"
            >
                {({ processing, errors }) => (
                    <>
                        <CardContent className="space-y-4">
                            {departmentRows.map((department, index) => (
                                <div
                                    key={`${index}-${department.name}`}
                                    className="rounded-2xl border p-4"
                                >
                                    <input
                                        type="hidden"
                                        name={`departments[${index}][is_clinical]`}
                                        value={
                                            department.is_clinical ? '1' : '0'
                                        }
                                    />

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label>Department name</Label>
                                            <Input
                                                name={`departments[${index}][name]`}
                                                value={department.name}
                                                onChange={(event) =>
                                                    setDepartmentRows(
                                                        departmentRows.map(
                                                            (row, rowIndex) =>
                                                                rowIndex ===
                                                                index
                                                                    ? {
                                                                          ...row,
                                                                          name: event
                                                                              .target
                                                                              .value,
                                                                      }
                                                                    : row,
                                                        ),
                                                    )
                                                }
                                                placeholder="Outpatient"
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `departments.${index}.name`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label>Location</Label>
                                            <Input
                                                name={`departments[${index}][location]`}
                                                value={department.location}
                                                onChange={(event) =>
                                                    setDepartmentRows(
                                                        departmentRows.map(
                                                            (row, rowIndex) =>
                                                                rowIndex ===
                                                                index
                                                                    ? {
                                                                          ...row,
                                                                          location:
                                                                              event
                                                                                  .target
                                                                                  .value,
                                                                      }
                                                                    : row,
                                                        ),
                                                    )
                                                }
                                                placeholder="Ground floor"
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `departments.${index}.location`
                                                    ]
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex items-start gap-3">
                                            <Checkbox
                                                checked={department.is_clinical}
                                                onCheckedChange={(checked) =>
                                                    setDepartmentRows(
                                                        departmentRows.map(
                                                            (row, rowIndex) =>
                                                                rowIndex ===
                                                                index
                                                                    ? {
                                                                          ...row,
                                                                          is_clinical:
                                                                              checked ===
                                                                              true,
                                                                      }
                                                                    : row,
                                                        ),
                                                    )
                                                }
                                            />
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium">
                                                    Clinical department
                                                </p>
                                                <p className="text-sm text-zinc-600">
                                                    Leave this on for OPD,
                                                    emergency, wards, and lab.
                                                </p>
                                            </div>
                                        </div>

                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                setDepartmentRows(
                                                    departmentRows.length > 1
                                                        ? departmentRows.filter(
                                                              (
                                                                  _row,
                                                                  rowIndex,
                                                              ) =>
                                                                  rowIndex !==
                                                                  index,
                                                          )
                                                        : departmentRows,
                                                )
                                            }
                                            disabled={
                                                departmentRows.length <= 1
                                            }
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            ))}

                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        setDepartmentRows([
                                            ...departmentRows,
                                            emptyOnboardingDepartment(),
                                        ])
                                    }
                                >
                                    <Plus className="h-4 w-4" />
                                    Add department
                                </Button>

                                <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                    <div className="flex items-start gap-2">
                                        <CheckCircle2 className="mt-0.5 h-4 w-4" />
                                        <span>
                                            Save departments now, then add the
                                            first operational staff member in
                                            the final onboarding step.
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <InputError
                                message={
                                    errors.departments as string | undefined
                                }
                            />
                        </CardContent>

                        <CardFooter className="flex flex-col items-stretch gap-3 border-t bg-zinc-50/70 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-zinc-600">
                                Start with the essentials. You can add more
                                departments later.
                            </p>
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                ) : null}
                                Save departments
                            </Button>
                        </CardFooter>
                    </>
                )}
            </Form>
        </Card>
    );
}
