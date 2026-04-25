<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Models\DispensingRecord;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final readonly class DispensingRecordPrintController implements HasMiddleware
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_dispensing.view', only: ['show']),
        ];
    }

    public function show(DispensingRecord $dispensingRecord): Response
    {
        abort_unless($dispensingRecord->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless(
            $this->inventoryLocationAccess->canAccessLocationForTypes(
                Auth::user(),
                $dispensingRecord->inventory_location_id,
                ['pharmacy'],
                $dispensingRecord->branch_id,
            ),
            403,
            'You do not have access to this dispensing record.',
        );

        $dispensingRecord->loadMissing([
            'visit:id,patient_id,facility_branch_id,visit_number',
            'visit.patient:id,patient_number,first_name,last_name,middle_name',
            'visit.branch:id,name,branch_code',
            'prescription:id,primary_diagnosis,pharmacy_notes',
            'inventoryLocation:id,name,location_code',
            'dispensedBy:id,staff_id,email',
            'dispensedBy.staff:id,first_name,last_name',
            'items:id,dispensing_record_id,prescription_item_id,inventory_item_id,substitution_inventory_item_id,prescribed_quantity,dispensed_quantity,balance_quantity,dispense_status,external_pharmacy,external_reason,notes',
            'items.inventoryItem:id,name,generic_name,brand_name,strength,dosage_form',
            'items.substitutionInventoryItem:id,name,generic_name',
            'items.prescriptionItem:id,dosage,frequency,route,duration_days,instructions',
            'items.allocations:id,dispensing_record_item_id,quantity,batch_number_snapshot,expiry_date_snapshot',
        ]);

        $dispensedBy = $dispensingRecord->dispensedBy;
        $staff = $dispensedBy?->staff;
        $visit = $dispensingRecord->visit;
        $patient = $visit?->patient;

        $dispenserName = $staff !== null
            ? mb_trim(sprintf('%s %s', $staff->first_name, $staff->last_name))
            : ($dispensedBy !== null ? $dispensedBy->email : 'Unknown');

        $filename = sprintf('dispense-slip-%s.pdf', Str::slug($dispensingRecord->dispense_number));

        $pdf = Pdf::loadView('print.dispensing-record', [
            'dispensingRecord' => $dispensingRecord,
            'visit' => $visit,
            'patient' => $patient,
            'dispenserName' => $dispenserName,
            'printedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream($filename);
    }
}
