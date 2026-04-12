import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';

type GeneralSettingsField = {
    field: string;
    label: string;
    description: string;
    type: 'boolean' | 'text' | 'select';
};

type GeneralSettingsSection = {
    title: string;
    description: string;
    fields: GeneralSettingsField[];
};

type GeneralSettingsPageProps = {
    sections: GeneralSettingsSection[];
    values: Record<string, boolean | string | null>;
    currencies: Array<{ value: string; label: string }>;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/administration/general-settings' },
    { title: 'General Settings', href: '/administration/general-settings' },
];

const textFieldPlaceholder = (field: string): string => {
    switch (field) {
        case 'patient_number_prefix':
            return 'PAT';
        case 'visit_number_prefix':
            return 'VIS';
        case 'receipt_number_prefix':
            return 'RCPT';
        case 'lab_request_prefix':
            return 'LAB';
        default:
            return 'Enter a value';
    }
};

export default function GeneralSettings({
    sections,
    values,
    currencies,
}: GeneralSettingsPageProps) {
    const form = useForm({
        require_payment_before_consultation:
            Boolean(values.require_payment_before_consultation),
        require_payment_before_laboratory:
            Boolean(values.require_payment_before_laboratory),
        require_payment_before_pharmacy: Boolean(
            values.require_payment_before_pharmacy,
        ),
        require_payment_before_procedures: Boolean(
            values.require_payment_before_procedures,
        ),
        allow_insured_bypass_upfront_payment: Boolean(
            values.allow_insured_bypass_upfront_payment,
        ),
        default_currency_id:
            typeof values.default_currency_id === 'string'
                ? values.default_currency_id
                : '',
        patient_number_prefix:
            typeof values.patient_number_prefix === 'string'
                ? values.patient_number_prefix
                : '',
        visit_number_prefix:
            typeof values.visit_number_prefix === 'string'
                ? values.visit_number_prefix
                : '',
        receipt_number_prefix:
            typeof values.receipt_number_prefix === 'string'
                ? values.receipt_number_prefix
                : '',
        lab_request_prefix:
            typeof values.lab_request_prefix === 'string'
                ? values.lab_request_prefix
                : '',
        enable_batch_tracking_when_dispensing: Boolean(
            values.enable_batch_tracking_when_dispensing,
        ),
        allow_partial_dispense: Boolean(values.allow_partial_dispense),
        require_review_before_lab_release: Boolean(
            values.require_review_before_lab_release,
        ),
        require_approval_before_lab_release: Boolean(
            values.require_approval_before_lab_release,
        ),
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="General Settings" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-3xl space-y-2">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">
                                General Settings
                            </h1>
                            <p className="mt-2 text-sm text-muted-foreground">
                                These are the first facility-wide operational
                                rules under Administration. They are saved per
                                tenant and give us a stable foundation before we
                                wire each rule deeper into every workflow.
                            </p>
                        </div>
                    </div>

                    <Button
                        onClick={() =>
                            form.patch('/administration/general-settings')
                        }
                        disabled={form.processing}
                    >
                        {form.processing ? (
                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                            <Save className="mr-2 h-4 w-4" />
                        )}
                        Save Settings
                    </Button>
                </div>

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader>
                        <CardTitle>First Release Scope</CardTitle>
                        <CardDescription>
                            This first version covers the high-value rules we
                            agreed to start with: payment-before-service,
                            default currency, numbering, pharmacy dispensing
                            behavior, and laboratory release requirements.
                        </CardDescription>
                    </CardHeader>
                </Card>

                {sections.map((section) => (
                    <Card
                        key={section.title}
                        className="border-none shadow-sm ring-1 ring-border/50"
                    >
                        <CardHeader>
                            <CardTitle>{section.title}</CardTitle>
                            <CardDescription>
                                {section.description}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            {section.fields.map((field) => {
                                if (field.type === 'boolean') {
                                    const checked = Boolean(
                                        form.data[
                                            field.field as keyof typeof form.data
                                        ],
                                    );

                                    return (
                                        <div
                                            key={field.field}
                                            className="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800"
                                        >
                                            <div className="space-y-1">
                                                <Label
                                                    htmlFor={field.field}
                                                    className="text-sm font-semibold"
                                                >
                                                    {field.label}
                                                </Label>
                                                <p className="text-sm text-muted-foreground">
                                                    {field.description}
                                                </p>
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            field.field as keyof typeof form.errors
                                                        ]
                                                    }
                                                />
                                            </div>
                                            <Switch
                                                id={field.field}
                                                checked={checked}
                                                onCheckedChange={(value) =>
                                                    form.setData(
                                                        field.field as keyof typeof form.data,
                                                        value,
                                                    )
                                                }
                                            />
                                        </div>
                                    );
                                }

                                if (field.type === 'select') {
                                    const selectedValue =
                                        (form.data[
                                            field.field as keyof typeof form.data
                                        ] as string) || '';

                                    return (
                                        <div
                                            key={field.field}
                                            className="grid gap-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800"
                                        >
                                            <Label
                                                htmlFor={field.field}
                                                className="text-sm font-semibold"
                                            >
                                                {field.label}
                                            </Label>
                                            <p className="text-sm text-muted-foreground">
                                                {field.description}
                                            </p>
                                            <SearchableSelect
                                                options={currencies}
                                                value={selectedValue}
                                                onValueChange={(value) =>
                                                    form.setData(
                                                        field.field as keyof typeof form.data,
                                                        value,
                                                    )
                                                }
                                                inputId={field.field}
                                                placeholder="Choose a default currency"
                                                emptyMessage="No currencies found."
                                                allowClear
                                            />
                                            <InputError
                                                message={
                                                    form.errors[
                                                        field.field as keyof typeof form.errors
                                                    ]
                                                }
                                            />
                                        </div>
                                    );
                                }

                                return (
                                    <div
                                        key={field.field}
                                        className="grid gap-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800"
                                    >
                                        <Label
                                            htmlFor={field.field}
                                            className="text-sm font-semibold"
                                        >
                                            {field.label}
                                        </Label>
                                        <p className="text-sm text-muted-foreground">
                                            {field.description}
                                        </p>
                                        <Input
                                            id={field.field}
                                            value={
                                                (form.data[
                                                    field.field as keyof typeof form.data
                                                ] as string) || ''
                                            }
                                            onChange={(event) =>
                                                form.setData(
                                                    field.field as keyof typeof form.data,
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={textFieldPlaceholder(
                                                field.field,
                                            )}
                                            className="max-w-sm"
                                        />
                                        <InputError
                                            message={
                                                form.errors[
                                                    field.field as keyof typeof form.errors
                                                ]
                                            }
                                        />
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>
                ))}

                <div className="flex justify-end">
                    <Button
                        onClick={() =>
                            form.patch('/administration/general-settings')
                        }
                        disabled={form.processing}
                    >
                        {form.processing ? (
                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                            <Save className="mr-2 h-4 w-4" />
                        )}
                        Save Settings
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
