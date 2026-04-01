import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { AlertCircle, Info } from 'lucide-react';
import { useState } from 'react';

interface Allergy {
    id: string;
    allergen_name: string;
    severity: string;
    reaction?: string | null;
}

export function AllergyAlert({
    allergies,
}: {
    allergies: Allergy[] | null | undefined;
}) {
    const [open, setOpen] = useState(false);

    if (!allergies || allergies.length === 0) return null;

    const getSeverityColor = (severity: string) => {
        const s = severity.toLowerCase();
        if (s.includes('life')) return 'bg-red-600 text-white hover:bg-red-700';
        if (s.includes('severe'))
            return 'bg-orange-500 text-white hover:bg-orange-600';
        if (s.includes('moderate'))
            return 'bg-amber-500 text-black hover:bg-amber-600';
        return 'bg-blue-500 text-white hover:bg-blue-600';
    };

    return (
        <>
            <Button
                variant="destructive"
                size="sm"
                className="h-5 animate-pulse rounded-full px-2 text-[9px] font-bold tracking-tight uppercase"
                onClick={() => setOpen(true)}
            >
                Has Allergies
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="border-none bg-white shadow-2xl sm:max-w-[500px]">
                    <DialogHeader>
                        <div className="flex items-center gap-2 text-destructive">
                            <AlertCircle className="h-5 w-5" />
                            <DialogTitle className="text-xl font-bold">
                                Documented Allergies
                            </DialogTitle>
                        </div>
                        <DialogDescription>
                            Review the following allergies carefully before
                            clinical intervention.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="mt-4 overflow-hidden rounded-lg border">
                        <Table>
                            <TableHeader className="bg-zinc-50">
                                <TableRow>
                                    <TableHead className="font-bold">
                                        Allergen
                                    </TableHead>
                                    <TableHead className="font-bold">
                                        Severity
                                    </TableHead>
                                    <TableHead className="font-bold">
                                        Reaction
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {allergies.map((allergy) => (
                                    <TableRow key={allergy.id}>
                                        <TableCell className="font-medium">
                                            {allergy.allergen_name}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                className={getSeverityColor(
                                                    allergy.severity,
                                                )}
                                            >
                                                {allergy.severity.replace(
                                                    '_',
                                                    ' ',
                                                )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {allergy.reaction ||
                                                'Not specified'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="mt-4 flex items-start gap-3 rounded-md border border-red-100 bg-red-50 p-3 text-sm text-red-900">
                        <Info className="mt-0.5 h-4 w-4 shrink-0" />
                        <p>
                            Verify these allergies with the patient or their
                            guardian before prescribing medication.
                        </p>
                    </div>

                    <div className="flex justify-end pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Close
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
