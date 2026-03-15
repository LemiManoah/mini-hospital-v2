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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type DrugEditPageProps } from '@/types/drug';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function DrugEdit({
    drug,
    categories,
    dosageForms,
}: DrugEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Drugs', href: '/drugs' },
        { title: 'Edit Drug', href: `/drugs/${drug.id}/edit` },
    ];
    const [category, setCategory] = useState(drug.category);
    const [dosageForm, setDosageForm] = useState(drug.dosage_form);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Drug: ${drug.generic_name}`} />
            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Edit Drug</h1>
                        <p className="text-sm text-muted-foreground">
                            Update catalog details for {drug.generic_name}.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/drugs">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={`/drugs/${drug.id}`}
                        method="put"
                        onSuccess={() =>
                            toast.success('Drug updated successfully.')
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="category"
                                    value={category}
                                />
                                <input
                                    type="hidden"
                                    name="dosage_form"
                                    value={dosageForm}
                                />
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="generic_name">
                                            Generic Name
                                        </Label>
                                        <Input
                                            id="generic_name"
                                            name="generic_name"
                                            defaultValue={drug.generic_name}
                                            required
                                        />
                                        <InputError
                                            message={errors.generic_name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="brand_name">
                                            Brand Name
                                        </Label>
                                        <Input
                                            id="brand_name"
                                            name="brand_name"
                                            defaultValue={drug.brand_name ?? ''}
                                        />
                                        <InputError
                                            message={errors.brand_name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="drug_code">
                                            Drug Code
                                        </Label>
                                        <Input
                                            id="drug_code"
                                            name="drug_code"
                                            defaultValue={drug.drug_code}
                                            required
                                        />
                                        <InputError
                                            message={errors.drug_code}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="strength">
                                            Strength
                                        </Label>
                                        <Input
                                            id="strength"
                                            name="strength"
                                            defaultValue={drug.strength}
                                            required
                                        />
                                        <InputError message={errors.strength} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Category</Label>
                                        <Select
                                            value={category}
                                            onValueChange={setCategory}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categories.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.category} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Dosage Form</Label>
                                        <Select
                                            value={dosageForm}
                                            onValueChange={setDosageForm}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select dosage form" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {dosageForms.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.dosage_form}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="unit">Unit</Label>
                                        <Input
                                            id="unit"
                                            name="unit"
                                            defaultValue={drug.unit}
                                            required
                                        />
                                        <InputError message={errors.unit} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="manufacturer">
                                            Manufacturer
                                        </Label>
                                        <Input
                                            id="manufacturer"
                                            name="manufacturer"
                                            defaultValue={
                                                drug.manufacturer ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.manufacturer}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="schedule_class">
                                            Schedule Class
                                        </Label>
                                        <Input
                                            id="schedule_class"
                                            name="schedule_class"
                                            defaultValue={
                                                drug.schedule_class ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.schedule_class}
                                        />
                                    </div>
                                    <div className="flex items-center gap-2 pt-8">
                                        <input
                                            id="is_controlled"
                                            name="is_controlled"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={drug.is_controlled}
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_controlled"
                                            className="font-normal"
                                        >
                                            Controlled drug
                                        </Label>
                                    </div>
                                    <div className="flex items-center gap-2 pt-8">
                                        <input
                                            id="is_active"
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={drug.is_active}
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="font-normal"
                                        >
                                            Active for use
                                        </Label>
                                    </div>
                                </div>
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="therapeutic_classes">
                                            Therapeutic Classes
                                        </Label>
                                        <Input
                                            id="therapeutic_classes"
                                            name="therapeutic_classes"
                                            defaultValue={
                                                drug.therapeutic_classes?.join(
                                                    ', ',
                                                ) ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.therapeutic_classes}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="contraindications">
                                            Contraindications
                                        </Label>
                                        <Textarea
                                            id="contraindications"
                                            name="contraindications"
                                            rows={3}
                                            defaultValue={
                                                drug.contraindications ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.contraindications}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="interactions">
                                            Interactions
                                        </Label>
                                        <Textarea
                                            id="interactions"
                                            name="interactions"
                                            rows={3}
                                            defaultValue={
                                                drug.interactions ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.interactions}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="side_effects">
                                            Side Effects
                                        </Label>
                                        <Textarea
                                            id="side_effects"
                                            name="side_effects"
                                            rows={3}
                                            defaultValue={
                                                drug.side_effects ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.side_effects}
                                        />
                                    </div>
                                </div>
                                <div className="flex gap-3 border-t pt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? (
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                        ) : (
                                            <Save className="mr-2 h-4 w-4" />
                                        )}
                                        Save Changes
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        type="button"
                                        asChild
                                    >
                                        <Link href="/drugs">Cancel</Link>
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
