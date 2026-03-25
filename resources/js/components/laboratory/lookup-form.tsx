import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, PlusCircle, Save } from 'lucide-react';
import { toast } from 'sonner';

type LookupFormProps = {
    title: string;
    heading: string;
    description: string;
    breadcrumbs: BreadcrumbItem[];
    action: string;
    method: 'post' | 'put';
    backHref: string;
    submitLabel: string;
    successMessage: string;
    values?: {
        name?: string;
        code?: string | null;
        description?: string | null;
        is_active?: boolean;
    };
    codeLabel?: string;
};

export function LookupForm({
    title,
    heading,
    description,
    breadcrumbs,
    action,
    method,
    backHref,
    submitLabel,
    successMessage,
    values,
    codeLabel,
}: LookupFormProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">{heading}</h1>
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={backHref}>Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={action}
                        method={method}
                        onSuccess={() => toast.success(successMessage)}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            autoFocus
                                            required
                                            defaultValue={values?.name ?? ''}
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    {codeLabel ? (
                                        <div className="grid gap-2">
                                            <Label htmlFor="code">
                                                {codeLabel}
                                            </Label>
                                            <Input
                                                id="code"
                                                name="code"
                                                required
                                                defaultValue={
                                                    values?.code ?? ''
                                                }
                                            />
                                            <InputError message={errors.code} />
                                        </div>
                                    ) : null}
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            rows={4}
                                            defaultValue={
                                                values?.description ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <div className="flex items-center gap-2 md:col-span-2">
                                        <input
                                            id="is_active"
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={
                                                values?.is_active ?? true
                                            }
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="font-normal"
                                        >
                                            Active
                                        </Label>
                                    </div>
                                </div>
                                <div className="flex gap-3 border-t pt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? (
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                        ) : method === 'post' ? (
                                            <PlusCircle className="mr-2 h-4 w-4" />
                                        ) : (
                                            <Save className="mr-2 h-4 w-4" />
                                        )}
                                        {submitLabel}
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        type="button"
                                        asChild
                                    >
                                        <Link href={backHref}>Cancel</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}
