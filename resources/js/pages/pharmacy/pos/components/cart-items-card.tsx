import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ShoppingCart } from 'lucide-react';
import { CartItemRow } from './cart-item-row';
import { type ActiveCart } from './types';

export function CartItemsCard({ activeCart }: { activeCart: ActiveCart }) {
    return (
        <Card className="rounded-2xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <CardHeader className="pb-4">
                <div className="flex items-center justify-between gap-4">
                    <CardTitle className="text-lg">Cart</CardTitle>
                    <ShoppingCart className="h-5 w-5 text-slate-400" />
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {activeCart.items.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-slate-200 py-10 text-center dark:border-slate-800">
                        <ShoppingCart className="mx-auto h-8 w-8 text-slate-400" />
                        <p className="mt-4 text-sm font-medium text-slate-700 dark:text-slate-300">
                            Cart is empty
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {activeCart.items.map((item) => (
                            <CartItemRow
                                key={item.id}
                                cartId={activeCart.id}
                                item={item}
                            />
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
