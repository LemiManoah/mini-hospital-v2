import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { MapPin } from 'lucide-react';
import { type ActiveCart } from './types';

interface SaleScreenCardProps {
    activeCart: ActiveCart;
}

export function SaleScreenCard({ activeCart }: SaleScreenCardProps) {
    return (
        <Card className="rounded-2xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <CardHeader className="pb-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <CardTitle className="text-lg">Sale Screen</CardTitle>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {activeCart.cart_number}
                        </p>
                    </div>
                    {activeCart.inventory_location && (
                        <div className="flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-800 dark:text-slate-300">
                            <MapPin className="h-3.5 w-3.5" />
                            {activeCart.inventory_location.name}
                        </div>
                    )}
                </div>
            </CardHeader>
            <CardContent>
                <div className="space-y-3 text-sm">
                    <div className="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Items in cart</span>
                        <span className="font-medium text-slate-950 dark:text-slate-100">
                            {activeCart.items.length}
                        </span>
                    </div>

                    <div className="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Customer</span>
                        <span className="font-medium text-slate-950 dark:text-slate-100">
                            {activeCart.customer_name?.trim()
                                ? activeCart.customer_name
                                : 'Walk-in'}
                        </span>
                    </div>

                    <div className="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span>Phone</span>
                        <span className="font-medium text-slate-950 dark:text-slate-100">
                            {activeCart.customer_phone?.trim()
                                ? activeCart.customer_phone
                                : 'Not captured'}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
