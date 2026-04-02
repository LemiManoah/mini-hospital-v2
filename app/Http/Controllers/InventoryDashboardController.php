<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryDashboardController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_items.view', only: ['index']),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('inventory/dashboard', [
            'stats' => [
                'total_items' => InventoryItem::query()->count(),
                'active_items' => InventoryItem::query()->where('is_active', true)->count(),
                'drug_items' => InventoryItem::query()->drugs()->count(),
                'expirable_items' => InventoryItem::query()->where('expires', true)->count(),
                'total_locations' => InventoryLocation::query()->count(),
                'dispensing_locations' => InventoryLocation::query()->where('is_dispensing_point', true)->count(),
            ],
        ]);
    }
}
