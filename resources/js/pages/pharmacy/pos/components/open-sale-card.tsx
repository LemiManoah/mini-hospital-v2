import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ArrowRight } from 'lucide-react';
import { type DispensingLocation } from './types';

interface OpenSaleCardProps {
    dispensingLocations: DispensingLocation[];
    data: {
        inventory_location_id: string;
        notes: string;
    };
    errors: Partial<Record<'inventory_location_id' | 'notes', string>>;
    processing: boolean;
    onSubmit: (event: React.FormEvent) => void;
    onLocationChange: (value: string) => void;
    onFieldChange: (field: 'notes', value: string) => void;
}

export function OpenSaleCard({
    dispensingLocations,
    data,
    errors,
    processing,
    onSubmit,
    onLocationChange,
    onFieldChange,
}: OpenSaleCardProps) {
    return (
        <Card className="rounded-2xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <CardHeader>
                <CardTitle className="text-lg">Start Sale</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={onSubmit} className="grid gap-5 lg:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="inventory_location_id">
                            Dispensing location
                        </Label>
                        <Select
                            value={data.inventory_location_id || undefined}
                            onValueChange={onLocationChange}
                        >
                            <SelectTrigger id="inventory_location_id">
                                <SelectValue placeholder="Select location" />
                            </SelectTrigger>
                            <SelectContent>
                                {dispensingLocations.map((location) => (
                                    <SelectItem
                                        key={location.id}
                                        value={location.id}
                                    >
                                        {location.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.inventory_location_id} />
                    </div>

                    <div className="space-y-2 lg:col-span-2">
                        <Label htmlFor="cart_notes">Notes</Label>
                        <Input
                            id="cart_notes"
                            value={data.notes}
                            onChange={(event) =>
                                onFieldChange('notes', event.target.value)
                            }
                            placeholder="Optional note..."
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end lg:col-span-2">
                        <Button
                            type="submit"
                            className="rounded-lg"
                            disabled={processing}
                        >
                            {processing ? 'Opening...' : 'Start Sale'}
                            <ArrowRight className="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
