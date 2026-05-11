import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link, router } from '@inertiajs/react';
import { History, PauseCircle } from 'lucide-react';
import { type ActiveCart } from './types';

export function PosPageHeader({
    activeCart,
}: {
    activeCart: ActiveCart | null;
}) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-slate-950 dark:text-slate-100">
                        Sales Screen
                    </h1>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Walk-in pharmacy sales
                    </p>

                    <div className="mt-4 flex flex-wrap items-center gap-2">
                        <Badge
                            variant="secondary"
                            className="rounded-full bg-slate-100 px-3 py-1 text-slate-700 dark:bg-slate-900 dark:text-slate-300"
                        >
                            Pharmacy POS
                        </Badge>
                        {activeCart ? (
                            <>
                                <Badge className="rounded-full bg-emerald-600 px-3 py-1 text-white">
                                    {activeCart.cart_number}
                                </Badge>
                                {activeCart.inventory_location && (
                                    <Badge
                                        variant="outline"
                                        className="rounded-full"
                                    >
                                        {activeCart.inventory_location.name}
                                    </Badge>
                                )}
                            </>
                        ) : (
                            <Badge variant="outline" className="rounded-full">
                                Ready to start
                            </Badge>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {activeCart && (
                        <Button
                            variant="outline"
                            className="rounded-xl"
                            onClick={() =>
                                router.post(
                                    `/pharmacy/pos/carts/${activeCart.id}/hold`,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <PauseCircle className="mr-2 h-4 w-4" />
                            Hold Sale
                        </Button>
                    )}
                    <Button variant="outline" className="rounded-xl" asChild>
                        <Link href="/pharmacy/pos/history">
                            <History className="mr-2 h-4 w-4" />
                            Sales History
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    );
}
