import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import { PlayCircle } from 'lucide-react';
import { type HeldCart } from './types';

export function HeldCartsCard({ heldCarts }: { heldCarts: HeldCart[] }) {
    return (
        <Card className="rounded-2xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <CardHeader>
                <CardTitle className="text-lg">Held Sales</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {heldCarts.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-slate-200 p-5 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        No held carts.
                    </div>
                ) : (
                    heldCarts.map((heldCart) => (
                        <div
                            key={heldCart.id}
                            className="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-800"
                        >
                            <div className="min-w-0">
                                <p className="font-semibold text-slate-900 dark:text-slate-100">
                                    {heldCart.cart_number}
                                </p>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {heldCart.customer_name ??
                                        'Walk-in customer'}
                                </p>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                className="rounded-lg"
                                onClick={() =>
                                    router.delete(
                                        `/pharmacy/pos/carts/${heldCart.id}/hold`,
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <PlayCircle className="mr-1.5 h-3.5 w-3.5" />
                                Resume
                            </Button>
                        </div>
                    ))
                )}
            </CardContent>
        </Card>
    );
}
