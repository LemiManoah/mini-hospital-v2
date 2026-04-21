<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Models\LabRequestItem;
use App\Support\ActiveBranchWorkspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

final readonly class LabResultPrintController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_requests.view', only: ['show']),
        ];
    }

    public function show(LabRequestItem $labRequestItem): Response
    {
        $labRequest = $labRequestItem->request()->firstOrFail();
        $this->activeBranchWorkspace->authorizeModel($labRequest);

        abort_unless(
            $labRequestItem->result_visible,
            403,
            'Only released laboratory results can be printed.',
        );

        $labRequestItem->loadMissing([
            'test:id,test_code,test_name',
            'specimen:id,lab_request_item_id,accession_number,specimen_type_name,collected_at,outside_sample,outside_sample_origin',
            'request:id,visit_id,requested_by,request_date,priority,clinical_notes,facility_branch_id',
            'request.requestedBy:id,first_name,last_name',
            'request.visit:id,visit_number,patient_id,facility_branch_id',
            'request.visit.patient:id,patient_number,first_name,last_name,middle_name,date_of_birth,age,age_units,gender,phone_number',
            'request.visit.branch:id,name,branch_code',
            'resultEntry:id,lab_request_item_id,entered_at,reviewed_at,approved_at,released_by,released_at,result_notes,review_notes,approval_notes,approved_by',
            'resultEntry.approvedBy:id,first_name,last_name',
            'resultEntry.releasedBy:id,first_name,last_name',
            'resultEntry.values:id,lab_result_entry_id,lab_test_result_parameter_id,label,value_numeric,value_text,unit,gender,age_min,age_max,reference_range,sort_order',
        ]);

        $visit = $labRequestItem->request?->visit;
        $patient = $visit?->patient;
        $test = $labRequestItem->test;
        $visitNumber = $visit !== null ? $visit->visit_number : 'visit';
        $testName = $test !== null ? $test->test_name : 'result';
        $filename = sprintf(
            'lab-result-%s-%s.pdf',
            $visitNumber,
            Str::slug($testName),
        );

        $pdf = Pdf::loadView('print.lab-result', [
            'labRequestItem' => $labRequestItem,
            'labRequest' => $labRequest,
            'visit' => $visit,
            'patient' => $patient,
            'test' => $test,
            'printedAt' => now(),
            'printedBy' => request()->user(),
        ])->setPaper('a4');

        return $pdf->stream($filename);
    }
}
