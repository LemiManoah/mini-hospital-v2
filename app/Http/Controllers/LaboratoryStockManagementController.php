<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LaboratoryStockManagementController
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless(
            $user?->can('inventory_items.view')
            || $user?->can('inventory_requisitions.view')
            || $user?->can('goods_receipts.view'),
            403,
        );

        return Inertia::render('laboratory/stock-management', [
            'sections' => [
                [
                    'title' => 'Lab Stock',
                    'description' => 'View laboratory stock balances across the lab locations in the active branch.',
                    'href' => '/laboratory/stock',
                    'permission' => 'inventory_items.view',
                ],
                [
                    'title' => 'Lab Requisitions',
                    'description' => 'Create and track the lab requisitions sent to the main store for fulfillment.',
                    'href' => '/laboratory/requisitions',
                    'permission' => 'inventory_requisitions.view',
                ],
                [
                    'title' => 'Lab Movements',
                    'description' => 'Review stock movement history affecting the laboratory inventory locations.',
                    'href' => '/laboratory/movements',
                    'permission' => 'inventory_items.view',
                ],
                [
                    'title' => 'Lab Receipts',
                    'description' => 'Receive and review stock delivered directly into laboratory inventory locations.',
                    'href' => '/laboratory/receipts',
                    'permission' => 'goods_receipts.view',
                ],
            ],
        ]);
    }
}
