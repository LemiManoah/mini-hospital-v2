<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Enums\StockMovementType;
use App\Models\InventoryRequisition;
use App\Models\StockMovement;
use App\Support\InventoryRequisitionAccess;
use App\Support\InventoryWorkspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final readonly class InventoryRequisitionPrintController implements HasMiddleware
{
    public function __construct(
        private InventoryRequisitionAccess $inventoryRequisitionAccess,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_requisitions.view', only: ['show']),
        ];
    }

    public function show(Request $request, InventoryRequisition $requisition): Response
    {
        $workspace = InventoryWorkspace::fromRequest($request);

        abort_unless(
            $this->inventoryRequisitionAccess->canView(Auth::user(), $requisition, $workspace),
            403,
            'You do not have access to this requisition.',
        );

        abort_unless(
            $this->inventoryRequisitionAccess->matchesWorkspace($requisition, $workspace),
            404,
            'This requisition does not belong to the selected inventory workspace.',
        );

        $requisition->load([
            'branch:id,name,branch_code',
            'fulfillingLocation:id,name,location_code',
            'requestingLocation:id,name,location_code',
            'items.inventoryItem:id,name,generic_name',
        ]);

        $issueHistory = StockMovement::query()
            ->with('inventoryBatch:id,batch_number,expiry_date')
            ->where('source_document_type', InventoryRequisition::class)
            ->where('source_document_id', $requisition->id)
            ->where('movement_type', StockMovementType::RequisitionOut)
            ->oldest('occurred_at')
            ->get()
            ->groupBy('source_line_id')
            ->map(static fn (Collection $movements): array => $movements
                ->map(static fn (StockMovement $movement): array => [
                    'quantity' => abs((float) $movement->quantity),
                    'batch_number' => $movement->inventoryBatch?->batch_number,
                    'expiry_date' => $movement->inventoryBatch?->expiry_date?->toDateString(),
                    'occurred_at' => $movement->occurred_at?->toIso8601String(),
                ])
                ->values()
                ->all())
            ->all();

        $pdf = Pdf::loadView('print.inventory-requisition', [
            'requisition' => $requisition,
            'issueHistory' => $issueHistory,
            'printedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream(sprintf(
            'inventory-requisition-%s.pdf',
            $requisition->requisition_number,
        ));
    }
}
