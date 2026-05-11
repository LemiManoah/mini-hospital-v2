import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { formatMoney, type ActiveCart } from './types';

export function OrderSummaryCard({ activeCart }: { activeCart: ActiveCart }) {
    return (
        <Card className="rounded-2xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <CardHeader className="pb-4">
                <CardTitle className="text-lg">Order Summary</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-3 text-sm">
                    <div className="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Subtotal</span>
                        <span className="font-medium text-slate-900 dark:text-slate-100">
                            {formatMoney(activeCart.gross_amount)}
                        </span>
                    </div>
                    <div className="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Discount</span>
                        <span className="font-medium text-emerald-700 dark:text-emerald-300">
                            -{formatMoney(activeCart.discount_amount)}
                        </span>
                    </div>
                </div>

                <div className="border-t border-slate-200 pt-4 dark:border-slate-800">
                    <div className="flex items-end justify-between">
                        <div>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Total payable
                            </p>
                            <p className="mt-1 text-3xl font-semibold tracking-tight text-slate-950 dark:text-slate-100">
                                {formatMoney(activeCart.total_amount)}
                            </p>
                        </div>
                        <Badge className="rounded-full bg-emerald-600 px-3 py-1 text-white">
                            Ready
                        </Badge>
                    </div>
                </div>

                <div className="grid gap-2">
                    <Button
                        className="h-11 rounded-lg"
                        disabled={activeCart.items.length === 0}
                        asChild
                    >
                        <Link
                            href={`/pharmacy/pos/carts/${activeCart.id}/checkout`}
                        >
                            Proceed to Checkout
                            <ArrowRight className="ml-2 h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
