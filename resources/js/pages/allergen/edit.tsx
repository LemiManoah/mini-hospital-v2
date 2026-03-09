import AllergenController from '@/actions/App/Http/Controllers/AllergenController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Allergen } from '@/types/allergen';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, FlaskConical, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface AllergenEditProps {
    allergen: Allergen;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Allergens', href: AllergenController.index.url() },
    { title: 'Edit Allergen', href: '#' },
];

export default function AllergenEdit({ allergen }: AllergenEditProps) {
    const [type, setType] = useState(allergen.type);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Allergen: ${allergen.name}`} />

            <div className="mt-4 mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 px-4">
                <div className="flex flex-col gap-1 w-full">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <FlaskConical className="h-6 w-6 text-indigo-500" />
                        Edit Allergen: {allergen.name}
                    </h2>
                    <p className="text-muted-foreground">
                        Update allergen classification or details.
                    </p>
                </div>
            </div>

            <div className="m-2 rounded border bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <Form
                    {...AllergenController.update.form({ allergen })}
                    onSuccess={() => toast.success('Allergen updated successfully.')}
                    className="p-6 space-y-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name" className="text-sm font-semibold">
                                        Allergen Name
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={allergen.name}
                                        placeholder="e.g. Penicillin, Peanuts"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="type" className="text-sm font-semibold">
                                        Type
                                    </Label>
                                    <Select value={type} onValueChange={(value) => setType(value as Allergen['type'])}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="medication">Medication</SelectItem>
                                            <SelectItem value="food">Food</SelectItem>
                                            <SelectItem value="environmental">Environmental</SelectItem>
                                            <SelectItem value="latex">Latex</SelectItem>
                                            <SelectItem value="contrast">Contrast Dye</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <input type="hidden" name="type" value={type} />
                                    <InputError message={errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description" className="text-sm font-semibold">
                                        Description (Optional)
                                    </Label>
                                    <Textarea
                                        id="description"
                                        name="description"
                                        defaultValue={allergen.description ?? ''}
                                        placeholder="Additional details about this allergen..."
                                        className="min-h-[100px]"
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </div>

                            <div className="flex items-center justify-start gap-3 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                                <Button type="submit" disabled={processing} className="min-w-[140px]">
                                    {processing ? (
                                        <LoaderCircle className="h-4 w-4 animate-spin mr-2" />
                                    ) : (
                                        <Save className="h-4 w-4 mr-2" />
                                    )}
                                    Save Changes
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href={AllergenController.index.url()}>Cancel</Link>
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
