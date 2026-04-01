import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import {
    type LabTestCatalog,
    type LabTestCatalogFormPageProps,
    type LabTestCatalogResultOption,
    type LabTestCatalogResultParameter,
} from '@/types/lab-test-catalog';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { toast } from 'sonner';

type LabTestCatalogFormData = {
    test_name: string;
    test_code: string;
    lab_test_category_id: string;
    specimen_type_ids: string[];
    result_type_id: string;
    description: string;
    base_price: string;
    is_active: boolean;
    result_options: LabTestCatalogResultOption[];
    result_parameters: LabTestCatalogResultParameter[];
};

interface LabTestCatalogFormProps extends LabTestCatalogFormPageProps {
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    successMessage: string;
    cancelHref: string;
    labTestCatalog?: LabTestCatalog;
}

const blankResultOption = (): LabTestCatalogResultOption => ({
    label: '',
});

const blankResultParameter = (): LabTestCatalogResultParameter => ({
    label: '',
    unit: '',
    gender: 'both',
    age_min: null,
    age_max: null,
    reference_range: '',
    value_type: 'numeric',
});

const resultParameterValueTypes = [
    { value: 'numeric', label: 'Numeric' },
    { value: 'text', label: 'Text' },
] as const;

const genderOptions = [
    { value: 'both', label: 'Both' },
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
] as const;

export default function LabTestCatalogForm({
    action,
    method,
    submitLabel,
    successMessage,
    cancelHref,
    labTestCatalog,
    categories,
    specimenTypes,
    resultTypes,
}: LabTestCatalogFormProps) {
    const form = useForm<LabTestCatalogFormData>({
        test_name: labTestCatalog?.test_name ?? '',
        test_code: labTestCatalog?.test_code ?? '',
        lab_test_category_id:
            labTestCatalog?.lab_test_category_id ?? categories[0]?.value ?? '',
        specimen_type_ids:
            labTestCatalog?.specimen_type_ids ??
            labTestCatalog?.specimen_types?.map(
                (specimenType) => specimenType.id,
            ) ??
            [],
        result_type_id:
            labTestCatalog?.result_type_id ?? resultTypes[0]?.value ?? '',
        description: labTestCatalog?.description ?? '',
        base_price:
            labTestCatalog?.base_price !== undefined
                ? String(labTestCatalog.base_price)
                : '0',
        is_active: labTestCatalog?.is_active ?? true,
        result_options:
            (labTestCatalog?.result_options?.length ?? 0) > 0
                ? labTestCatalog!.result_options!.map((option) => ({
                      id: option.id,
                      label: option.label,
                  }))
                : [blankResultOption()],
        result_parameters:
            (labTestCatalog?.result_parameters?.length ?? 0) > 0
                ? labTestCatalog!.result_parameters!.map((parameter) => ({
                      id: parameter.id,
                      label: parameter.label,
                      unit: parameter.unit ?? '',
                      gender: parameter.gender ?? 'both',
                      age_min: parameter.age_min ?? null,
                      age_max: parameter.age_max ?? null,
                      reference_range: parameter.reference_range ?? '',
                      value_type: parameter.value_type ?? 'numeric',
                  }))
                : [blankResultParameter()],
    });

    const selectedResultType =
        resultTypes.find(
            (resultType) => resultType.value === form.data.result_type_id,
        ) ?? null;
    const selectedResultTypeCode = selectedResultType?.code ?? null;

    const toggleSpecimenType = (
        specimenTypeId: string,
        checked: boolean,
    ): void => {
        form.setData(
            'specimen_type_ids',
            checked
                ? [...form.data.specimen_type_ids, specimenTypeId]
                : form.data.specimen_type_ids.filter(
                      (id) => id !== specimenTypeId,
                  ),
        );
    };

    const updateResultOption = (index: number, label: string): void => {
        const nextOptions = [...form.data.result_options];
        nextOptions[index] = { ...nextOptions[index], label };
        form.setData('result_options', nextOptions);
    };

    const addResultOption = (): void => {
        form.setData('result_options', [
            ...form.data.result_options,
            blankResultOption(),
        ]);
    };

    const removeResultOption = (index: number): void => {
        const nextOptions = form.data.result_options.filter(
            (_, itemIndex) => itemIndex !== index,
        );
        form.setData(
            'result_options',
            nextOptions.length > 0 ? nextOptions : [blankResultOption()],
        );
    };

    const updateResultParameter = (
        index: number,
        field: keyof LabTestCatalogResultParameter,
        value: string | number | null,
    ): void => {
        const nextParameters = [...form.data.result_parameters];
        nextParameters[index] = {
            ...nextParameters[index],
            [field]: value,
        };
        form.setData('result_parameters', nextParameters);
    };

    const addResultParameter = (): void => {
        form.setData('result_parameters', [
            ...form.data.result_parameters,
            blankResultParameter(),
        ]);
    };

    const removeResultParameter = (index: number): void => {
        const nextParameters = form.data.result_parameters.filter(
            (_, itemIndex) => itemIndex !== index,
        );
        form.setData(
            'result_parameters',
            nextParameters.length > 0
                ? nextParameters
                : [blankResultParameter()],
        );
    };

    const submit = (): void => {
        const options = {
            preserveScroll: true,
            onSuccess: () => toast.success(successMessage),
        };

        if (method === 'put') {
            form.put(action, options);

            return;
        }

        form.post(action, options);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Lab Test Definition</CardTitle>
                <CardDescription>
                    Configure the orderable test, valid specimen types, and the
                    result structure the laboratory team will use.
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-6">
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="test_name">Test Name</Label>
                        <Input
                            id="test_name"
                            value={form.data.test_name}
                            onChange={(event) =>
                                form.setData('test_name', event.target.value)
                            }
                            aria-invalid={Boolean(form.errors.test_name)}
                        />
                        <InputError message={form.errors.test_name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="test_code">Test Code</Label>
                        <Input
                            id="test_code"
                            value={form.data.test_code}
                            onChange={(event) =>
                                form.setData('test_code', event.target.value)
                            }
                            aria-invalid={Boolean(form.errors.test_code)}
                        />
                        <InputError message={form.errors.test_code} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Category</Label>
                        <Select
                            value={form.data.lab_test_category_id}
                            onValueChange={(value) =>
                                form.setData('lab_test_category_id', value)
                            }
                        >
                            <SelectTrigger
                                aria-invalid={Boolean(
                                    form.errors.lab_test_category_id,
                                )}
                            >
                                <SelectValue placeholder="Select category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    {categories.map((category) => (
                                        <SelectItem
                                            key={category.value}
                                            value={category.value}
                                        >
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <InputError
                            message={form.errors.lab_test_category_id}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="base_price">Base Price</Label>
                        <Input
                            id="base_price"
                            type="number"
                            min="0"
                            step="0.01"
                            value={form.data.base_price}
                            onChange={(event) =>
                                form.setData('base_price', event.target.value)
                            }
                            aria-invalid={Boolean(form.errors.base_price)}
                        />
                        <InputError message={form.errors.base_price} />
                    </div>
                    <div className="grid gap-2 md:col-span-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            rows={4}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            placeholder="Optional context for clinicians and lab staff."
                            aria-invalid={Boolean(form.errors.description)}
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="is_active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked === true)
                            }
                        />
                        <Label htmlFor="is_active" className="font-normal">
                            Active for ordering
                        </Label>
                    </div>
                </div>

                <Separator />

                <div className="flex flex-col gap-4">
                    <div className="flex flex-col gap-1">
                        <div className="flex items-center gap-2">
                            <h2 className="text-base font-semibold">
                                Accepted Specimen Types
                            </h2>
                            <Badge variant="outline">
                                {form.data.specimen_type_ids.length} selected
                            </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Choose every specimen type this test can be run on.
                        </p>
                    </div>
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {specimenTypes.map((specimenType) => {
                            const checked =
                                form.data.specimen_type_ids.includes(
                                    specimenType.value,
                                );

                            return (
                                <label
                                    key={specimenType.value}
                                    className="flex cursor-pointer items-start gap-3 rounded-lg border bg-card p-4 text-card-foreground"
                                >
                                    <Checkbox
                                        checked={checked}
                                        onCheckedChange={(value) =>
                                            toggleSpecimenType(
                                                specimenType.value,
                                                value === true,
                                            )
                                        }
                                    />
                                    <span className="font-medium">
                                        {specimenType.label}
                                    </span>
                                </label>
                            );
                        })}
                    </div>
                    <InputError message={form.errors.specimen_type_ids} />
                </div>

                <Separator />

                <div className="flex flex-col gap-4">
                    <div className="flex flex-col gap-1">
                        <h2 className="text-base font-semibold">
                            Result Configuration
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Define how results should be entered for this test.
                        </p>
                    </div>

                    <div className="grid gap-2 md:max-w-md">
                        <Label>Result Type</Label>
                        <Select
                            value={form.data.result_type_id}
                            onValueChange={(value) =>
                                form.setData('result_type_id', value)
                            }
                        >
                            <SelectTrigger
                                aria-invalid={Boolean(
                                    form.errors.result_type_id,
                                )}
                            >
                                <SelectValue placeholder="Select result type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    {resultTypes.map((resultType) => (
                                        <SelectItem
                                            key={resultType.value}
                                            value={resultType.value}
                                        >
                                            {resultType.label}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.result_type_id} />
                    </div>

                    {selectedResultType ? (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-semibold">
                                    {selectedResultType.label}
                                </CardTitle>
                                <CardDescription>
                                    {selectedResultType.description ??
                                        'No additional description is available for this result type.'}
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    ) : null}

                    {selectedResultTypeCode === 'defined_option' ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>Allowed Result Options</CardTitle>
                                <CardDescription>
                                    Add the exact choices technicians should use
                                    when entering this result.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {form.data.result_options.map(
                                    (option, index) => (
                                        <div
                                            key={option.id ?? `option-${index}`}
                                            className="flex flex-col gap-3 rounded-lg border p-4 md:flex-row md:items-end"
                                        >
                                            <div className="grid flex-1 gap-2">
                                                <Label
                                                    htmlFor={`result-option-${index}`}
                                                >
                                                    Option Label
                                                </Label>
                                                <Input
                                                    id={`result-option-${index}`}
                                                    value={option.label}
                                                    onChange={(event) =>
                                                        updateResultOption(
                                                            index,
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="e.g. Positive"
                                                    aria-invalid={Boolean(
                                                        form.errors[
                                                            `result_options.${index}.label`
                                                        ],
                                                    )}
                                                />
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `result_options.${index}.label`
                                                        ]
                                                    }
                                                />
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    removeResultOption(index)
                                                }
                                            >
                                                Remove
                                            </Button>
                                        </div>
                                    ),
                                )}
                                <InputError
                                    message={form.errors.result_options}
                                />
                            </CardContent>
                            <CardFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={addResultOption}
                                >
                                    Add Option
                                </Button>
                            </CardFooter>
                        </Card>
                    ) : null}

                    {selectedResultTypeCode === 'parameter_panel' ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>Panel Parameters</CardTitle>
                                <CardDescription>
                                    Add every parameter technicians should enter
                                    for this test panel.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {form.data.result_parameters.map(
                                    (parameter, index) => (
                                        <div
                                            key={
                                                parameter.id ??
                                                `parameter-${index}`
                                            }
                                            className="grid gap-4 rounded-lg border p-4 md:grid-cols-2 xl:grid-cols-4"
                                        >
                                            <div className="grid gap-2 xl:col-span-2">
                                                <Label
                                                    htmlFor={`parameter-label-${index}`}
                                                >
                                                    Parameter
                                                </Label>
                                                <Input
                                                    id={`parameter-label-${index}`}
                                                    value={parameter.label}
                                                    onChange={(event) =>
                                                        updateResultParameter(
                                                            index,
                                                            'label',
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="e.g. Hemoglobin"
                                                    aria-invalid={Boolean(
                                                        form.errors[
                                                            `result_parameters.${index}.label`
                                                        ],
                                                    )}
                                                />
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `result_parameters.${index}.label`
                                                        ]
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`parameter-unit-${index}`}
                                                >
                                                    Unit
                                                </Label>
                                                <Input
                                                    id={`parameter-unit-${index}`}
                                                    value={parameter.unit ?? ''}
                                                    onChange={(event) =>
                                                        updateResultParameter(
                                                            index,
                                                            'unit',
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="e.g. g/dL"
                                                />
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `result_parameters.${index}.unit`
                                                        ]
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Gender</Label>
                                                <Select
                                                    value={
                                                        parameter.gender ??
                                                        'both'
                                                    }
                                                    onValueChange={(value) =>
                                                        updateResultParameter(
                                                            index,
                                                            'gender',
                                                            value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select gender" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectGroup>
                                                            {genderOptions.map(
                                                                (option) => (
                                                                    <SelectItem
                                                                        key={
                                                                            option.value
                                                                        }
                                                                        value={
                                                                            option.value
                                                                        }
                                                                    >
                                                                        {
                                                                            option.label
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectGroup>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`parameter-age-min-${index}`}
                                                >
                                                    Min Age
                                                </Label>
                                                <Input
                                                    id={`parameter-age-min-${index}`}
                                                    type="number"
                                                    value={
                                                        parameter.age_min ?? ''
                                                    }
                                                    onChange={(event) =>
                                                        updateResultParameter(
                                                            index,
                                                            'age_min',
                                                            event.target
                                                                .value === ''
                                                                ? null
                                                                : parseInt(
                                                                      event
                                                                          .target
                                                                          .value,
                                                                  ),
                                                        )
                                                    }
                                                    placeholder="0"
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`parameter-age-max-${index}`}
                                                >
                                                    Max Age
                                                </Label>
                                                <Input
                                                    id={`parameter-age-max-${index}`}
                                                    type="number"
                                                    value={
                                                        parameter.age_max ?? ''
                                                    }
                                                    onChange={(event) =>
                                                        updateResultParameter(
                                                            index,
                                                            'age_max',
                                                            event.target
                                                                .value === ''
                                                                ? null
                                                                : parseInt(
                                                                      event
                                                                          .target
                                                                          .value,
                                                                  ),
                                                        )
                                                    }
                                                    placeholder="120"
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Value Type</Label>
                                                <Select
                                                    value={
                                                        parameter.value_type ??
                                                        'numeric'
                                                    }
                                                    onValueChange={(value) =>
                                                        updateResultParameter(
                                                            index,
                                                            'value_type',
                                                            value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select value type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectGroup>
                                                            {resultParameterValueTypes.map(
                                                                (valueType) => (
                                                                    <SelectItem
                                                                        key={
                                                                            valueType.value
                                                                        }
                                                                        value={
                                                                            valueType.value
                                                                        }
                                                                    >
                                                                        {
                                                                            valueType.label
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectGroup>
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `result_parameters.${index}.value_type`
                                                        ]
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2 xl:col-span-3">
                                                <Label
                                                    htmlFor={`parameter-reference-range-${index}`}
                                                >
                                                    Reference Range (Label)
                                                </Label>
                                                <Input
                                                    id={`parameter-reference-range-${index}`}
                                                    value={
                                                        parameter.reference_range ??
                                                        ''
                                                    }
                                                    onChange={(event) =>
                                                        updateResultParameter(
                                                            index,
                                                            'reference_range',
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="e.g. 12.0 - 16.0"
                                                />
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `result_parameters.${index}.reference_range`
                                                        ]
                                                    }
                                                />
                                            </div>
                                            <div className="flex items-end">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() =>
                                                        removeResultParameter(
                                                            index,
                                                        )
                                                    }
                                                >
                                                    Remove
                                                </Button>
                                            </div>
                                        </div>
                                    ),
                                )}
                                <InputError
                                    message={form.errors.result_parameters}
                                />
                            </CardContent>
                            <CardFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={addResultParameter}
                                >
                                    Add Parameter
                                </Button>
                            </CardFooter>
                        </Card>
                    ) : null}

                    {selectedResultTypeCode !== 'defined_option' &&
                    selectedResultTypeCode !== 'parameter_panel' ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuration Notes</CardTitle>
                                <CardDescription>
                                    {selectedResultTypeCode === 'free_entry'
                                        ? 'Free entry tests do not need predefined result options. Technicians will enter a direct value later in the result workflow.'
                                        : 'This result type does not need per-test option rows in the catalog yet. We can expand the downstream workflow for it when we implement result entry.'}
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    ) : null}
                </div>
            </CardContent>
            <CardFooter className="flex gap-3">
                <Button
                    type="button"
                    disabled={form.processing}
                    onClick={submit}
                >
                    {form.processing ? (
                        <LoaderCircle
                            data-icon="inline-start"
                            className="animate-spin"
                        />
                    ) : null}
                    {submitLabel}
                </Button>
                <Button type="button" variant="ghost" asChild>
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
            </CardFooter>
        </Card>
    );
}
