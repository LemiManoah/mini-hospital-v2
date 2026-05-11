import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type InventoryNavigationContext } from '@/types/inventory-navigation';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { CartItemsCard } from './components/cart-items-card';
import { HeldCartsCard } from './components/held-carts-card';
import { OpenSaleCard } from './components/open-sale-card';
import { OrderSummaryCard } from './components/order-summary-card';
import { PosPageHeader } from './components/pos-page-header';
import { PosStepper } from './components/pos-stepper';
import { ProductCatalogCard } from './components/product-catalog-card';
import { SaleScreenCard } from './components/sale-screen-card';
import {
    CATALOG_PAGE_SIZE,
    type ActiveCart,
    type DispensingLocation,
    type HeldCart,
    type SearchableItem,
} from './components/types';

interface PharmacyPosIndexProps {
    navigation: InventoryNavigationContext;
    dispensingLocations: DispensingLocation[];
    activeCart: ActiveCart | null;
    heldCarts: HeldCart[];
    searchableItems: SearchableItem[];
    defaults: {
        inventory_location_id: string | null;
    };
}

const breadcrumbs = (
    navigation: InventoryNavigationContext,
): BreadcrumbItem[] => [
    { title: navigation.section_title, href: navigation.section_href },
    { title: 'Pharmacy POS', href: '/pharmacy/pos' },
];

export default function PharmacyPosIndex({
    navigation,
    dispensingLocations,
    activeCart,
    heldCarts,
    searchableItems,
    defaults,
}: PharmacyPosIndexProps) {
    const [itemSearch, setItemSearch] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('All Items');
    const [catalogPage, setCatalogPage] = useState(1);

    const openCartForm = useForm({
        inventory_location_id: defaults.inventory_location_id ?? '',
        notes: '',
    });

    const categoryOptions = useMemo(() => {
        const forms = Array.from(
            new Set(
                searchableItems
                    .map((item) => item.dosage_form?.trim())
                    .filter((value): value is string => Boolean(value)),
            ),
        ).slice(0, 6);

        return ['All Items', ...forms];
    }, [searchableItems]);

    const filteredItems = useMemo(() => {
        return searchableItems.filter((item) => {
            const search = itemSearch.trim().toLowerCase();
            const matchesSearch =
                search === '' ||
                item.name.toLowerCase().includes(search) ||
                (item.generic_name ?? '').toLowerCase().includes(search) ||
                (item.brand_name ?? '').toLowerCase().includes(search);

            const matchesCategory =
                selectedCategory === 'All Items' ||
                (item.dosage_form ?? '').toLowerCase() ===
                    selectedCategory.toLowerCase();

            return matchesSearch && matchesCategory;
        });
    }, [itemSearch, searchableItems, selectedCategory]);

    const totalPages = Math.max(
        1,
        Math.ceil(filteredItems.length / CATALOG_PAGE_SIZE),
    );
    const currentPage = Math.min(catalogPage, totalPages);
    const pagedItems = filteredItems.slice(
        (currentPage - 1) * CATALOG_PAGE_SIZE,
        currentPage * CATALOG_PAGE_SIZE,
    );

    useEffect(() => {
        setCatalogPage(1);
    }, [itemSearch, selectedCategory]);

    const handleOpenCart = (event: React.FormEvent) => {
        event.preventDefault();
        openCartForm.post('/pharmacy/pos', { preserveScroll: true });
    };

    const handleAddItem = (item: SearchableItem) => {
        if (!activeCart) {
            return;
        }

        router.post(
            `/pharmacy/pos/carts/${activeCart.id}/items`,
            {
                inventory_item_id: item.id,
                quantity: '1',
                unit_price: item.unit_price.toFixed(2),
                discount_amount: '0',
                notes: '',
            },
            { preserveScroll: true },
        );
    };

    const stepIndex = !activeCart ? 0 : activeCart.items.length === 0 ? 1 : 2;

    return (
        <AppLayout breadcrumbs={breadcrumbs(navigation)}>
            <Head title="Pharmacy POS" />

            <div className="flex h-full flex-col gap-6 bg-slate-50/40 p-4 md:p-6 dark:bg-transparent">
                <PosPageHeader activeCart={activeCart} />

                {!activeCart ? (
                    <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                        <OpenSaleCard
                            dispensingLocations={dispensingLocations}
                            data={openCartForm.data}
                            errors={openCartForm.errors}
                            processing={openCartForm.processing}
                            onSubmit={handleOpenCart}
                            onLocationChange={(value) =>
                                openCartForm.setData(
                                    'inventory_location_id',
                                    value,
                                )
                            }
                            onFieldChange={(field, value) =>
                                openCartForm.setData(field, value)
                            }
                        />
                        <HeldCartsCard heldCarts={heldCarts} />
                    </div>
                ) : (
                    <div className="grid gap-6 xl:grid-cols-[minmax(0,1.08fr)_380px]">
                        <ProductCatalogCard
                            itemSearch={itemSearch}
                            selectedCategory={selectedCategory}
                            categoryOptions={categoryOptions}
                            pagedItems={pagedItems}
                            filteredItemsCount={filteredItems.length}
                            currentPage={currentPage}
                            totalPages={totalPages}
                            onSearchChange={setItemSearch}
                            onCategoryChange={setSelectedCategory}
                            onAddItem={handleAddItem}
                            onPreviousPage={() =>
                                setCatalogPage(currentPage - 1)
                            }
                            onNextPage={() => setCatalogPage(currentPage + 1)}
                        />

                        <div className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                            <SaleScreenCard activeCart={activeCart} />
                            <CartItemsCard activeCart={activeCart} />
                            <OrderSummaryCard activeCart={activeCart} />
                        </div>
                    </div>
                )}

                <PosStepper currentStep={stepIndex} />
            </div>
        </AppLayout>
    );
}
