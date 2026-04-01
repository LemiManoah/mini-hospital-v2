import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { useForm } from '@inertiajs/react';
import { Loader2, Plus } from 'lucide-react';
import { useState } from 'react';

interface Allergen {
    id: string;
    name: string;
    type: string;
}

interface Option {
    value: string;
    label: string;
}

export function AllergenModal({
    open,
    onOpenChange,
    patientId,
    allergens = [],
    severityOptions = [],
    reactionOptions = [],
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    patientId: string;
    allergens: Allergen[];
    severityOptions: Option[];
    reactionOptions: Option[];
}) {
    const [mode, setMode] = useState<'assign' | 'create'>('assign');

    const assignForm = useForm({
        allergen_id: '',
        severity: severityOptions[0]?.value ?? 'mild',
        reaction: reactionOptions[0]?.value ?? 'rash',
        notes: '',
        is_active: true,
    });

    const createForm = useForm({
        name: '',
        type: 'medication',
        description: '',
    });

    const onAssignSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        assignForm.post(`/patients/${patientId}/allergies`, {
            onSuccess: () => {
                onOpenChange(false);
                assignForm.reset();
            },
        });
    };

    const onCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/allergens', {
            onSuccess: () => {
                setMode('assign');
                createForm.reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="border-none bg-white shadow-2xl sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">
                        {mode === 'assign'
                            ? 'Record Patient Allergy'
                            : 'Create New Allergen'}
                    </DialogTitle>
                    <DialogDescription>
                        {mode === 'assign'
                            ? 'Assign a known allergen to this patient profile.'
                            : 'Add a new allergen to the system catalog.'}
                    </DialogDescription>
                </DialogHeader>

                {mode === 'assign' ? (
                    <form onSubmit={onAssignSubmit} className="space-y-5 py-2">
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="allergen_id">Allergen</Label>
                                <Button
                                    type="button"
                                    variant="link"
                                    className="h-auto p-0 text-xs"
                                    onClick={() => setMode('create')}
                                >
                                    <Plus className="mr-1 h-3 w-3" />
                                    Not in list? Create new
                                </Button>
                            </div>
                            <Select
                                value={assignForm.data.allergen_id}
                                onValueChange={(value) =>
                                    assignForm.setData('allergen_id', value)
                                }
                            >
                                <SelectTrigger className="bg-white">
                                    <SelectValue placeholder="Select an allergen" />
                                </SelectTrigger>
                                <SelectContent className="max-h-[300px] bg-white">
                                    {allergens.map((a) => (
                                        <SelectItem key={a.id} value={a.id}>
                                            {a.name} ({a.type})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={assignForm.errors.allergen_id}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="severity">Severity</Label>
                                <Select
                                    value={assignForm.data.severity}
                                    onValueChange={(value) =>
                                        assignForm.setData('severity', value)
                                    }
                                >
                                    <SelectTrigger className="bg-white">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white">
                                        {severityOptions.map((opt) => (
                                            <SelectItem
                                                key={opt.value}
                                                value={opt.value}
                                            >
                                                {opt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={assignForm.errors.severity}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="reaction">
                                    Typical Reaction
                                </Label>
                                <Select
                                    value={assignForm.data.reaction}
                                    onValueChange={(value) =>
                                        assignForm.setData('reaction', value)
                                    }
                                >
                                    <SelectTrigger className="bg-white">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white">
                                        {reactionOptions.map((opt) => (
                                            <SelectItem
                                                key={opt.value}
                                                value={opt.value}
                                            >
                                                {opt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={assignForm.errors.reaction}
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="notes">Clinical Notes</Label>
                            <Textarea
                                id="notes"
                                className="bg-white"
                                placeholder="Add any specific details about this patient's reaction..."
                                value={assignForm.data.notes}
                                onChange={(e) =>
                                    assignForm.setData('notes', e.target.value)
                                }
                            />
                            <InputError message={assignForm.errors.notes} />
                        </div>

                        <div className="flex justify-end gap-3 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={assignForm.processing}
                            >
                                {assignForm.processing && (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                )}
                                Record Allergy
                            </Button>
                        </div>
                    </form>
                ) : (
                    <form onSubmit={onCreateSubmit} className="space-y-5 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="name">Allergen Name</Label>
                            <Input
                                id="name"
                                className="bg-white"
                                placeholder="e.g. Penicillin, Peanuts..."
                                value={createForm.data.name}
                                onChange={(e) =>
                                    createForm.setData('name', e.target.value)
                                }
                            />
                            <InputError message={createForm.errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="type">Type</Label>
                            <Select
                                value={createForm.data.type}
                                onValueChange={(value) =>
                                    createForm.setData('type', value)
                                }
                            >
                                <SelectTrigger className="bg-white">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent className="bg-white">
                                    <SelectItem value="medication">
                                        Medication
                                    </SelectItem>
                                    <SelectItem value="food">Food</SelectItem>
                                    <SelectItem value="environmental">
                                        Environmental
                                    </SelectItem>
                                    <SelectItem value="latex">Latex</SelectItem>
                                    <SelectItem value="contrast">
                                        Contrast Dye
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={createForm.errors.type} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">
                                Description (Optional)
                            </Label>
                            <Textarea
                                id="description"
                                className="bg-white"
                                value={createForm.data.description}
                                onChange={(e) =>
                                    createForm.setData(
                                        'description',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={createForm.errors.description}
                            />
                        </div>

                        <div className="flex justify-end gap-3 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setMode('assign')}
                            >
                                Back to Assign
                            </Button>
                            <Button
                                type="submit"
                                disabled={createForm.processing}
                            >
                                {createForm.processing && (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                )}
                                Create Allergen
                            </Button>
                        </div>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
