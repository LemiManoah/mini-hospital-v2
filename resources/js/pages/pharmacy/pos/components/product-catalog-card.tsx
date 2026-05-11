import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Package2, Plus, Search } from 'lucide-react';
import {
    CATALOG_PAGE_SIZE,
    formatMoney,
    formatQuantity,
    stockTone,
    type SearchableItem,
} from './types';

interface ProductCatalogCardProps {
    itemSearch: string;
    selectedCategory: string;
    categoryOptions: string[];
    pagedItems: SearchableItem[];
    filteredItemsCount: number;
    currentPage: number;
    totalPages: number;
    onSearchChange: (value: string) => void;
    onCategoryChange: (value: string) => void;
    onAddItem: (item: SearchableItem) => void;
    onPreviousPage: () => void;
    onNextPage: () => void;
}

export function ProductCatalogCard({
    itemSearch,
    selectedCategory,
    categoryOptions,
    pagedItems,
    filteredItemsCount,
    currentPage,
    totalPages,
    onSearchChange,
    onCategoryChange,
    onAddItem,
    onPreviousPage,
    onNextPage,
}: ProductCatalogCardProps) {
    return (
        <Card className="rounded-2xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <CardHeader className="pb-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <CardTitle className="text-lg">Product Catalog</CardTitle>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge
                            variant="secondary"
                            className="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-900"
                        >
                            {filteredItemsCount} items
                        </Badge>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-5">
                <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            value={itemSearch}
                            onChange={(event) =>
                                onSearchChange(event.target.value)
                            }
                            placeholder="Search medicine..."
                            className="h-11 rounded-xl pl-10"
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {categoryOptions.map((option) => (
                            <button
                                key={option}
                                type="button"
                                onClick={() => onCategoryChange(option)}
                                className={[
                                    'rounded-lg border px-4 py-2 text-sm font-medium transition',
                                    option === selectedCategory
                                        ? 'border-sky-600 bg-sky-600 text-white'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-sky-200 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:text-slate-100',
                                ].join(' ')}
                            >
                                {option}
                            </button>
                        ))}
                    </div>
                </div>

                {pagedItems.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-slate-200 py-16 text-center dark:border-slate-800">
                        <Package2 className="mx-auto h-8 w-8 text-slate-400" />
                        <p className="mt-4 text-sm font-medium text-slate-700 dark:text-slate-300">
                            No medicines match this search.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {pagedItems.map((item) => {
                            const tone = stockTone(item.available_quantity);

                            return (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() => onAddItem(item)}
                                    className={`group rounded-xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md dark:bg-slate-950/40 ${tone.border}`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="line-clamp-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                {item.name}
                                            </p>
                                            <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                {[
                                                    item.generic_name,
                                                    item.dosage_form,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' / ') ||
                                                    'General item'}
                                            </p>
                                        </div>
                                        <Badge
                                            className={`rounded-full ${tone.badge}`}
                                        >
                                            {tone.label}
                                        </Badge>
                                    </div>

                                    <div className="mt-6 flex items-end justify-between gap-4">
                                        <div>
                                            <p className="text-lg font-semibold text-slate-950 dark:text-slate-100">
                                                {formatMoney(item.unit_price)}
                                            </p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                Stock{' '}
                                                {formatQuantity(
                                                    item.available_quantity,
                                                )}
                                            </p>
                                        </div>
                                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700 transition group-hover:bg-sky-600 group-hover:text-white dark:bg-slate-900 dark:text-slate-200">
                                            <Plus className="h-4 w-4" />
                                        </div>
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                )}

                {totalPages > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Showing {(currentPage - 1) * CATALOG_PAGE_SIZE + 1}{' '}
                            to{' '}
                            {Math.min(
                                currentPage * CATALOG_PAGE_SIZE,
                                filteredItemsCount,
                            )}{' '}
                            of {filteredItemsCount}
                        </p>
                        <div className="flex items-center gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                className="rounded-lg"
                                disabled={currentPage === 1}
                                onClick={onPreviousPage}
                            >
                                Previous
                            </Button>
                            <Badge
                                variant="secondary"
                                className="rounded-lg px-3 py-1"
                            >
                                Page {currentPage} / {totalPages}
                            </Badge>
                            <Button
                                size="sm"
                                variant="outline"
                                className="rounded-lg"
                                disabled={currentPage === totalPages}
                                onClick={onNextPage}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
