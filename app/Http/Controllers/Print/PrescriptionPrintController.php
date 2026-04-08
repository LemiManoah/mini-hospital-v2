<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Enums\PrescriptionStatus;
use App\Models\Prescription;
use App\Support\ActiveBranchWorkspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

final readonly class PrescriptionPrintController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:visits.view', only: ['show']),
        ];
    }

    public function show(Prescription $prescription): Response
    {
        $prescription->loadMissing([
            'visit:id,patient_id,facility_branch_id,visit_number,visit_type,registered_at',
            'visit.patient:id,patient_number,first_name,last_name,middle_name,date_of_birth,age,age_units,gender,phone_number',
            'visit.branch:id,name,branch_code',
            'prescribedBy:id,first_name,last_name',
            'items:id,prescription_id,inventory_item_id,dosage,frequency,route,duration_days,quantity,instructions,is_prn,prn_reason,is_external_pharmacy,status,dispensed_at',
            'items.inventoryItem:id,name,generic_name,brand_name,strength,dosage_form',
        ]);

        $visit = $prescription->visit;
        abort_unless($visit !== null, 404);

        $this->activeBranchWorkspace->authorizeModel($visit);

        abort_unless(
            $prescription->status !== PrescriptionStatus::CANCELLED,
            403,
            'Cancelled prescriptions cannot be printed.',
        );

        $patient = $visit->patient;
        $filename = sprintf(
            'prescription-%s-%s.pdf',
            $visit->visit_number ?? 'visit',
            Str::slug($prescription->primary_diagnosis ?? 'prescription'),
        );

        $pdf = Pdf::loadView('print.prescription', [
            'prescription' => $prescription,
            'visit' => $visit,
            'patient' => $patient,
            'printedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream($filename);
    }
}
