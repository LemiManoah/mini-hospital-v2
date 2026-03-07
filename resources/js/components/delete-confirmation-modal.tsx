import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Form } from '@inertiajs/react';
import { LoaderCircle, Trash2 } from 'lucide-react';
import { ReactNode } from 'react';

interface Props {
    title: string;
    description: string;
    action: {
        action: string;
        method: 'get' | 'post' | 'put' | 'patch' | 'delete';
    };
    onSuccess?: () => void;
    trigger?: ReactNode;
}

export default function DeleteConfirmationModal({
    title,
    description,
    action,
    onSuccess,
    trigger,
}: Props) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                {trigger || (
                    <Button variant="destructive" size="sm">
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form
                    {...action}
                    onSuccess={onSuccess}
                    className="space-y-6"
                >
                    {({ processing, resetAndClearErrors }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button
                                    variant="secondary"
                                    onClick={() => resetAndClearErrors()}
                                >
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                variant="destructive"
                                disabled={processing}
                                type="submit"
                            >
                                {processing && (
                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                )}
                                Confirm Delete
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
